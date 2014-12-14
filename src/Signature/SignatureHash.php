<?php

namespace Bitcoin\Signature;

use Bitcoin\Util\Hash;
use Bitcoin\Util\Buffer;
use Bitcoin\Util\Parser;
use Bitcoin\Script\Script;
use Bitcoin\Transaction\TransactionInterface;
use Bitcoin\Transaction\TransactionOutputInterface;

/**
 * Class SigHashBuilder
 * @package Bitcoin
 */
class SignatureHash implements SignatureHashInterface
{
    /**
     * @var TransactionInterface
     */
    protected $transaction;

    /**
     * @var TransactionInterface
     */
    protected $copy;

    /**
     * @param TransactionInterface $transaction
     */
    public function __construct(TransactionInterface $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * @return TransactionInterface
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * Calculate the hash of the current transaction, when you are looking to
     * spend $txOut, and are signing $inputToSign. The SigHashType defaults to
     * SIGHASH_ALL, though SIGHASH_SINGLE, SIGHASH_NONE, SIGHASH_ANYONECANPAY
     * can be used.
     *
     * @param TransactionOutputInterface $txOut
     * @param $inputToSign
     * @param int $sighashType
     * @return Buffer
     * @throws \Exception
     */
    public function calculateHash(TransactionOutputInterface $txOut, $inputToSign, $sighashType = SignatureHashInterface::SIGHASH_ALL)
    {
        $this->copy     =  $this->getTransaction();
        $inputs         = &$this->copy->getInputsReference();
        $outputs        = &$this->copy->getOutputsReference();

        if ($inputToSign > count($inputs)) {
            throw new \Exception('Input does not exist');
        }

        // Default SIGHASH_ALL procedure: null all input scripts
        for ($i = 0; $i < count($inputs); $i++) {
            $inputs[$i]->setScriptBuf(new Buffer());
        }

        $inputs[$inputToSign]->setScript($txOut->getScript());

        if ($sighashType & 31 == SignatureHashInterface::SIGHASH_NONE) {
            // Set outputs to empty vector, and set sequence number of inputs to 0.

            $outputs = array();

            for ($i = 0; $i < count($inputs); $i++) {
                if ($i != $inputToSign) {
                    $inputs[$i]->setSequence(0);
                }
            }

        } else if ($sighashType & 31 == SignatureHashInterface::SIGHASH_SINGLE) {
            // Resize output array to $inputToSign + 1, set remaining scripts to null,
            // and set sequence's to zero.

            $nOutput = $inputToSign;
            if ($nOutput >= count($outputs)) {
                throw new \Exception("SignatureHash->calculateHash(): nOutput $nOutput is out of range");
            }

            // Resize..
            $outputs = array_slice($outputs, 0, ($nOutput+1));

            // Set to null
            for ($i = 0; $i < $nOutput; $i++) {
                $outputs[$i]->setScript(new Script());
            }

            // Let the others update at will
            for ($i = 0; $i < count($outputs); $i++) {
                if ($i != $inputToSign) {
                    $inputs[$i]->setSequence(0);
                }
            }
        }

        // This can happen regardless of whether it's ALL, NONE, or SINGLE
        if ($sighashType & 31 == SignatureHashInterface::SIGHASH_ANYONECANPAY) {
            $input  = $inputs[$inputToSign];
            $inputs = array($input);
        }

        // Serialize the TxCopy and append the 4 byte hashtype (little endian);
        $txParser = new Parser($this->copy->serialize('hex'));
        $txParser->writeInt(4, $sighashType, true);

        $hash     = Hash::sha256d($txParser->getBuffer()->serialize());
        $buffer   = Buffer::hex($hash);
        return $buffer;
    }
}
