<?php

namespace BitWasp\Bitcoin\Transaction\SignatureHash;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;

class V1Hasher
{
    /**
     * @var TransactionInterface
     */
    private $transaction;

    /**
     * @var int|string
     */
    private $amount;

    /**
     * V1Hasher constructor.
     * @param TransactionInterface $transaction
     * @param int|string $amount
     */
    public function __construct(TransactionInterface $transaction, $amount)
    {
        $this->transaction = $transaction;
        $this->amount = $amount;
    }

    /**
     * @param int $sighashType
     * @return Buffer|BufferInterface
     */
    public function hashPrevOuts($sighashType)
    {
        if (!($sighashType & SigHashInterface::ANYONECANPAY)) {
            $binary = '';
            foreach ($this->transaction->getInputs() as $input) {
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
        if (!($sighashType & SigHashInterface::ANYONECANPAY) && ($sighashType & 0x1f) != SigHashInterface::SINGLE && ($sighashType & 0x1f) != SigHashInterface::NONE) {
            $binary = '';
            foreach ($this->transaction->getInputs() as $input) {
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
        if (($sighashType & 0x1f) !== SigHashInterface::SINGLE && ($sighashType & 0x1f) != SigHashInterface::NONE) {
            $binary = '';
            foreach ($this->transaction->getOutputs() as $output) {
                $binary .= $output->getBinary();
            }
            return Hash::sha256d(new Buffer($binary));
        } elseif (($sighashType & 0x1f) == SigHashInterface::SINGLE && $inputToSign < count($this->transaction->getOutputs())) {
            return Hash::sha256($this->transaction->getOutput($inputToSign)->getBuffer());
        }

        return new Buffer('', 32);
    }

    /**
     * Calculate the hash of the current transaction, when you are looking to
     * spend $txOut, and are signing $inputToSign. The SigHashType defaults to
     * SIGHASH_ALL, though SIGHASH_SINGLE, SIGHASH_NONE, SIGHASH_ANYONECANPAY
     * can be used.
     *
     * @param ScriptInterface $txOutScript
     * @param int $inputToSign
     * @param int $sighashType
     * @return BufferInterface
     * @throws \Exception
     */
    public function calculate(ScriptInterface $txOutScript, $inputToSign, $sighashType = SigHashInterface::ALL)
    {
        $sighashType = (int) $sighashType;

        $hashPrevOuts = $this->hashPrevOuts($sighashType);
        $hashSequence = $this->hashSequences($sighashType);
        $hashOutputs = $this->hashOutputs($sighashType, $inputToSign);

        $input = $this->transaction->getInput($inputToSign);

        return Hash::sha256d(new Buffer(
            pack("V", $this->transaction->getVersion()) .
            $hashPrevOuts->getBinary() .
            $hashSequence->getBinary() .
            $input->getOutPoint()->getBinary() .
            ScriptFactory::create()->push($txOutScript->getBuffer())->getScript()->getBinary() .
            Buffer::int($this->amount, 8)->flip()->getBinary() .
            pack("V", $input->getSequence()) .
            $hashOutputs->getBinary() .
            pack("V", $this->transaction->getLockTime()) .
            pack("V", $sighashType)
        ));
    }
}
