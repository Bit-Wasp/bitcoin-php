<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\SignatureHash;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\PrecomputedData;
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
     * @var TransactionOutputSerializer
     */
    protected $outputSerializer;

    /**
     * @var OutPointSerializerInterface
     */
    protected $outpointSerializer;

    /**
     * @var PrecomputedData
     */
    protected $precomputedData;

    /**
     * V1Hasher constructor.
     * @param TransactionInterface $transaction
     * @param int $amount
     * @param PrecomputedData $precomputedData
     * @param OutPointSerializerInterface $outpointSerializer
     * @param TransactionOutputSerializer|null $outputSerializer
     */
    public function __construct(
        TransactionInterface $transaction,
        int $amount,
        PrecomputedData $precomputedData,
        OutPointSerializerInterface $outpointSerializer = null,
        TransactionOutputSerializer $outputSerializer = null
    ) {
        $this->amount = $amount;
        $this->precomputedData = $precomputedData;
        $this->outputSerializer = $outputSerializer ?: new TransactionOutputSerializer();
        $this->outpointSerializer = $outpointSerializer ?: new OutPointSerializer();
        if (!($precomputedData->isReady() && $precomputedData->haveSpentOutputs())) {
            throw new \RuntimeException("");
        }
        parent::__construct($transaction);
    }

    /**
     * Calculate the hash of the current transaction, when you are looking to
     * spend $txOut, and are signing $inputToSign. The SigHashType defaults to
     * SIGHASH_ALL
     *
     * Note: this function doesn't use txOutScript, as we have access to it via
     * spentOutputs.
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
            $ss .= $this->precomputedData->getPrevoutsSha256()->getBinary();
            $ss .= $this->precomputedData->getSpentAmountsSha256()->getBinary();
            $ss .= $this->precomputedData->getSequencesSha256()->getBinary();
        }
        if ($outputType === SigHash::TAPDEFAULT || $outputType === SigHash::ALL) {
            $ss .= $this->precomputedData->getOutputsSha256()->getBinary();
        }

        $scriptPubKey = $this->precomputedData->getSpentOutputs()[$inputToSign]->getScript()->getBuffer();
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
            $ss .= pack('P', $this->precomputedData->getSpentOutputs()[$inputToSign]->getValue());
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
