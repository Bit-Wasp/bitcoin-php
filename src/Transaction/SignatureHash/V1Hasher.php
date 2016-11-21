<?php

namespace BitWasp\Bitcoin\Transaction\SignatureHash;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffertools;

class V1Hasher extends SigHash
{
    /**
     * @var TransactionInterface
     */
    protected $transaction;

    /**
     * @var int
     */
    protected $amount;

    /**
     * V1Hasher constructor.
     * @param TransactionInterface $transaction
     * @param int $amount
     */
    public function __construct(TransactionInterface $transaction, $amount)
    {
        $this->amount = $amount;
        parent::__construct($transaction);
    }

    /**
     * @param int $sighashType
     * @return Buffer|BufferInterface
     */
    public function hashPrevOuts($sighashType)
    {
        if (!($sighashType & SigHash::ANYONECANPAY)) {
            $binary = '';
            foreach ($this->tx->getInputs() as $input) {
                $binary .= $input->getOutPoint()->getBinary();
            }
            return Hash::sha256d(new Buffer($binary));
        }

        return new Buffer('', 32);
    }

    /**
     * @param int $sighashType
     * @return Buffer|BufferInterface
     */
    public function hashSequences($sighashType)
    {
        if (!($sighashType & SigHash::ANYONECANPAY) && ($sighashType & 0x1f) !== SigHash::SINGLE && ($sighashType & 0x1f) !== SigHash::NONE) {
            $binary = '';
            foreach ($this->tx->getInputs() as $input) {
                $binary .= Buffer::int($input->getSequence())->flip()->getBinary();
            }

            return Hash::sha256d(new Buffer($binary));
        }

        return new Buffer('', 32);
    }

    /**
     * @param int $sighashType
     * @param int $inputToSign
     * @return Buffer|BufferInterface
     */
    public function hashOutputs($sighashType, $inputToSign)
    {
        if (($sighashType & 0x1f) !== SigHash::SINGLE && ($sighashType & 0x1f) !== SigHash::NONE) {
            $binary = '';
            foreach ($this->tx->getOutputs() as $output) {
                $binary .= $output->getBinary();
            }
            return Hash::sha256d(new Buffer($binary));
        } elseif (($sighashType & 0x1f) === SigHash::SINGLE && $inputToSign < count($this->tx->getOutputs())) {
            return Hash::sha256d($this->tx->getOutput($inputToSign)->getBuffer());
        }

        return new Buffer('', 32);
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
    public function calculate(ScriptInterface $txOutScript, $inputToSign, $sighashType = SigHash::ALL)
    {
        $sighashType = (int) $sighashType;

        $hashPrevOuts = $this->hashPrevOuts($sighashType);
        $hashSequence = $this->hashSequences($sighashType);
        $hashOutputs = $this->hashOutputs($sighashType, $inputToSign);
        $input = $this->tx->getInput($inputToSign);

        $scriptBuf = $txOutScript->getBuffer();
        $preimage = new Buffer(
            pack("V", $this->tx->getVersion()) .
            $hashPrevOuts->getBinary() .
            $hashSequence->getBinary() .
            $input->getOutPoint()->getBinary() .
            Buffertools::numToVarInt($scriptBuf->getSize())->getBinary() . $scriptBuf->getBinary() .
            Buffer::int($this->amount, 8)->flip()->getBinary() .
            pack("V", $input->getSequence()) .
            $hashOutputs->getBinary() .
            pack("V", $this->tx->getLockTime()) .
            pack("V", $sighashType)
        );

        return Hash::sha256d($preimage);
    }
}
