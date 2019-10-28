<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\SignatureHash;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializerInterface;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;

class TaprootHasher extends SigHash
{
    /**
     * @var TransactionInterface
     */
    protected $tx;

    /**
     * @var int
     */
    protected $amount;

    /**
     * @var array|TransactionOutputInterface[]
     */
    protected $spentOutputs;

    /**
     * @var TransactionOutputSerializer
     */
    protected $outputSerializer;

    /**
     * @var OutPointSerializerInterface
     */
    protected $outpointSerializer;

    /**
     * V1Hasher constructor.
     * @param TransactionInterface $transaction
     * @param int $amount
     * @param TransactionOutputInterface[] $txOuts
     * @param OutPointSerializerInterface $outpointSerializer
     * @param TransactionOutputSerializer|null $outputSerializer
     */
    public function __construct(
        TransactionInterface $transaction,
        int $amount,
        array $txOuts,
        OutPointSerializerInterface $outpointSerializer = null,
        TransactionOutputSerializer $outputSerializer = null
    ) {
        $this->amount = $amount;
        $this->spentOutputs = $txOuts;
        $this->outputSerializer = $outputSerializer ?: new TransactionOutputSerializer();
        $this->outpointSerializer = $outpointSerializer ?: new OutPointSerializer();
        parent::__construct($transaction);
    }

    /**
     * Same as V1Hasher, but with sha256 instead of sha256d
     * @param int $sighashType
     * @return BufferInterface
     */
    public function hashPrevOuts(int $sighashType): BufferInterface
    {
        if (!($sighashType & SigHash::ANYONECANPAY)) {
            $binary = '';
            foreach ($this->tx->getInputs() as $input) {
                $binary .= $this->outpointSerializer->serialize($input->getOutPoint())->getBinary();
            }
            return Hash::sha256(new Buffer($binary));
        }

        return new Buffer('', 32);
    }

    /**
     * Same as V1Hasher, but with sha256 instead of sha256d
     * @param int $sighashType
     * @return BufferInterface
     */
    public function hashSequences(int $sighashType): BufferInterface
    {
        if (!($sighashType & SigHash::ANYONECANPAY) && ($sighashType & 0x1f) !== SigHash::SINGLE && ($sighashType & 0x1f) !== SigHash::NONE) {
            $binary = '';
            foreach ($this->tx->getInputs() as $input) {
                $binary .= pack('V', $input->getSequence());
            }

            return Hash::sha256(new Buffer($binary));
        }

        return new Buffer('', 32);
    }

    /**
     * Same as V1Hasher, but with sha256 instead of sha256d
     * @param int $sighashType
     * @param int $inputToSign
     * @return BufferInterface
     */
    public function hashOutputs(int $sighashType, int $inputToSign): BufferInterface
    {
        if (($sighashType & 0x1f) !== SigHash::SINGLE && ($sighashType & 0x1f) !== SigHash::NONE) {
            $binary = '';
            foreach ($this->tx->getOutputs() as $output) {
                $binary .= $this->outputSerializer->serialize($output)->getBinary();
            }
            return Hash::sha256(new Buffer($binary));
        } elseif (($sighashType & 0x1f) === SigHash::SINGLE && $inputToSign < count($this->tx->getOutputs())) {
            return Hash::sha256($this->outputSerializer->serialize($this->tx->getOutput($inputToSign)));
        }

        return new Buffer('', 32);
    }

    /**
     * @param TransactionOutputInterface[] $txOuts
     * @return BufferInterface
     */
    public function hashSpentAmountsHash(array $txOuts): BufferInterface
    {
        $binary = '';
        foreach ($txOuts as $output) {
            $binary .= pack("P", $output->getValue());
        }
        return Hash::sha256(new Buffer($binary));
    }

    /**
     * Calculate the hash of the current transaction, when you are looking to
     * spend $txOut, and are signing $inputToSign. The SigHashType defaults to
     * SIGHASH_ALL
     *
     * @param ScriptInterface $txOutScript
     * @param int $inputToSign
     * @param int $sighashType
     * @return BufferInterface
     * @throws \Exception
     */
    public function calculate(
        ScriptInterface $txOutScript,
        int $inputToSign,
        int $sighashType = SigHash::ALL
    ): BufferInterface {
        if (($sighashType > 3) && ($sighashType < 0x81 || $sighashType > 0x83)) {
            throw new \RuntimeException("invalid hash type");
        }
        $epoch = 0;
        $input = $this->tx->getInput($inputToSign);

        $ss = '';
        $ss .= pack("C", $epoch);
        $ss .= pack('CVV', $sighashType, $this->tx->getVersion(), $this->tx->getLockTime());

        $inputType = $sighashType & SigHash::TAPINPUTMASK;
        $outputType = $sighashType & SigHash::TAPOUTPUTMASK;

        if ($inputType === SigHash::TAPDEFAULT) {
            $ss .= $this->hashPrevOuts($sighashType)->getBinary();
            $ss .= $this->hashSpentAmountsHash($this->spentOutputs)->getBinary();
            $ss .= $this->hashSequences($sighashType)->getBinary();
        }
        if ($outputType === SigHash::TAPDEFAULT || $outputType === SigHash::ALL) {
            $ss .= $this->hashOutputs($sighashType, $inputToSign)->getBinary();
        }

        $scriptPubKey = $this->spentOutputs[$inputToSign]->getScript()->getBuffer();
        $spendType = 0;
        $witnesses = $this->tx->getWitnesses();

        // todo: does back() == bottom()?
        $witness = new ScriptWitness();
        if (array_key_exists($inputToSign, $witnesses)) {
            $witness = $witnesses[$inputToSign];
            if ($witness->count() > 1 && $witness->bottom()->getSize() > 0 && ord($witness->bottom()->getBinary()[0]) === 0xff) {
                $spendType |= 1;
            }
        }

        $ss .= pack('C', $spendType);
        $ss .= Buffertools::numToVarIntBin($scriptPubKey->getSize()) . $scriptPubKey->getBinary();

        if ($inputType === SigHash::ANYONECANPAY) {
            $ss .= $this->outpointSerializer->serialize($input->getOutPoint())->getBinary();
            $ss .= pack('P', $this->spentOutputs[$inputToSign]->getValue());
            $ss .= pack('V', $input->getSequence());
        } else {
            $ss .= pack('V', $inputToSign);
        }
        if (($spendType & 2) != 0) {
            $ss .= Hash::sha256($witness->bottom())->getBinary();
        }

        if ($outputType == SigHash::SINGLE) {
            $outputs = $this->tx->getOutputs();
            if ($inputToSign >= count($outputs)) {
                throw new \RuntimeException("sighash single input > #outputs");
            }
            $ss .= Hash::sha256($this->outputSerializer->serialize($outputs[$inputToSign]))->getBinary();
        }

        return Hash::taggedSha256('TapSighash', new Buffer($ss));
    }
}
