<?php

namespace BitWasp\Bitcoin\Transaction\SignatureHash;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
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
     * @var int
     */
    private $nInputs;

    /**
     * @var int
     */
    private $nOutputs;

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
        $this->nInputs = count($this->transaction->getInputs());
        $this->nOutputs = count($this->transaction->getOutputs());
        $this->amount = $amount;
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
    public function calculate(ScriptInterface $txOutScript, $inputToSign, $sighashType = SigHash::ALL)
    {
        $sighashType = (int) $sighashType;
        $hashSequence = $hashOutputs = $hashPrevOuts = new Buffer('', 32);

        if (!($sighashType & SigHash::ANYONECANPAY)) {
            $hashPrevOuts = Hash::sha256d(new Buffer(implode("", array_map(function (TransactionInputInterface $input) {
                return $input->getOutPoint()->getBinary();
            }, $this->transaction->getInputs()->all()))));
        }

        if (!($sighashType & SigHash::ANYONECANPAY) && ($sighashType & 0x1f) != SigHash::SINGLE && ($sighashType & 0x1f) != SigHash::NONE) {
            $hashSequence = Hash::sha256d(new Buffer(implode("", array_map(function (TransactionInputInterface $input) {
                return Buffer::int($input->getSequence())->flip()->getBinary();
            }, $this->transaction->getInputs()->all()))));
        }

        if (($sighashType & 0x1f) !== SigHash::SINGLE && ($sighashType & 0x1f) != SigHash::NONE) {
            $hashOutputs = Hash::sha256d(new Buffer(implode("", array_map(function (TransactionOutputInterface $input) {
                return $input->getBinary();
            }, $this->transaction->getOutputs()->all()))));
        } elseif (($sighashType & 0x1f) == SigHash::SINGLE && $inputToSign < count($this->transaction->getOutputs())) {
            $hashOutputs = Hash::sha256($this->transaction->getOutput($inputToSign)->getBuffer());
        }

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
