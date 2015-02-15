<?php

namespace Afk11\Bitcoin\Signature;

use Afk11\Bitcoin\Crypto\Hash;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Script\Script;
use Afk11\Bitcoin\Script\ScriptInterface;
use Afk11\Bitcoin\Transaction\TransactionInterface;
use Afk11\Bitcoin\Transaction\TransactionOutputInterface;
use Afk11\Bitcoin\Transaction\TransactionInputCollection;
use Afk11\Bitcoin\Transaction\TransactionOutputCollection;

class SignatureHash implements SignatureHashInterface
{
    /**
     * @var TransactionInterface
     */
    protected $transaction;

    /**
     * @param TransactionInterface $transaction
     */
    public function __construct(TransactionInterface $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Calculate the hash of the current transaction, when you are looking to
     * spend $txOut, and are signing $inputToSign. The SigHashType defaults to
     * SIGHASH_ALL, though SIGHASH_SINGLE, SIGHASH_NONE, SIGHASH_ANYONECANPAY
     * can be used.
     *
     * @param ScriptInterface $txOutScript
     * @param $inputToSign
     * @param int $sighashType
     * @return Buffer
     * @throws \Exception
     */
    public function calculate(ScriptInterface $txOutScript, $inputToSign, $sighashType = SignatureHashInterface::SIGHASH_ALL)
    {
        $copy     = $this->transaction;
        $inputs   = $copy->getInputs();
        $outputs  = $copy->getOutputs();

        if ($inputToSign > count($inputs)) {
            throw new \Exception('Input does not exist');
        }

        // Default SIGHASH_ALL procedure: null all input scripts
        for ($i = 0; $i < count($inputs); $i++) {
            $inputs->getInput($i)->setScriptBuf(new Buffer());
        }

        $inputs->getInput($inputToSign)->setScript($txOutScript);

        if ($sighashType & 31 == SignatureHashInterface::SIGHASH_NONE) {
            // Set outputs to empty vector, and set sequence number of inputs to 0.

            $outputs = new TransactionOutputCollection();

            for ($i = 0; $i < count($inputs); $i++) {
                if ($i != $inputToSign) {
                    $inputs->getInput($i)->setSequence(0);
                }
            }

        } elseif ($sighashType & 31 == SignatureHashInterface::SIGHASH_SINGLE) {
            // Resize output array to $inputToSign + 1, set remaining scripts to null,
            // and set sequence's to zero.

            $nOutput = $inputToSign;
            if ($nOutput >= count($outputs)) {
                return Buffer::hex('01');
            }

            // Resize..
            $outputs = $outputs->slice(0, $nOutput + 1);

            // Set to null
            for ($i = 0; $i < $nOutput; $i++) {
                $outputs->getOutput($i)->setScript(new Script());
            }

            // Let the others update at will
            for ($i = 0; $i < count($outputs); $i++) {
                if ($i != $inputToSign) {
                    $inputs->getInput($i)->setSequence(0);
                }
            }
        }

        // This can happen regardless of whether it's ALL, NONE, or SINGLE
        if ($sighashType & 31 == SignatureHashInterface::SIGHASH_ANYONECANPAY) {
            $input  = $inputs->getInput($inputToSign);
            $inputs = new TransactionInputCollection([ $input ]);
        }

        // Serialize the TxCopy and append the 4 byte hashtype (little endian);
        $txParser = new Parser($copy->serialize('hex'));
        $txParser->writeInt(4, $sighashType, true);

        $hash     = Hash::sha256d($txParser->getBuffer()->serialize());
        $buffer   = Buffer::hex($hash);

        return $buffer;
    }
}
