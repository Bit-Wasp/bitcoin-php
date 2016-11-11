<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;

class SignatureHash implements SignatureHashInterface
{
    /**
     * @var TransactionInterface
     */
    private $transaction;

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
        $copy = $this->transaction->makeCopy();

        $inputs = $copy->getInputs();
        $outputs = $copy->getOutputs();

        if ($inputToSign > count($inputs)) {
            throw new \Exception('Input does not exist');
        }

        // Default SIGHASH_ALL procedure: null all input scripts
        $inputCount = count($inputs);
        for ($i = 0; $i < $inputCount; $i++) {
            $inputs->getInput($i)->setScript(new Script());
        }

        $inputs->getInput($inputToSign)->setScript($txOutScript);
        $math = Bitcoin::getMath();

        if ($math->bitwiseAnd($sighashType, 31) == SignatureHashInterface::SIGHASH_NONE) {
            // Set outputs to empty vector, and set sequence number of inputs to 0.
            $copy->setOutputs(new TransactionOutputCollection());

            // Let the others update at will. Set sequence of inputs we're not signing to 0.
            $inputCount = count($inputs);
            for ($i = 0; $i < $inputCount; $i++) {
                if ($math->cmp($i, $inputToSign) !== 0) {
                    $inputs->getInput($i)->setSequence(0);
                }
            }
        } elseif ($math->bitwiseAnd($sighashType, 31) == SignatureHashInterface::SIGHASH_SINGLE) {
            // Resize output array to $inputToSign + 1, set remaining scripts to null,
            // and set sequence's to zero.
            $nOutput = $inputToSign;

            if ($math->cmp($nOutput, count($outputs)) >= 0) {
                return Buffer::hex('0100000000000000000000000000000000000000000000000000000000000000');
            }

            // Resize..
            $outputs = $outputs->slice(0, $nOutput + 1)->getOutputs();

            // Set to null
            for ($i = 0; $i < $nOutput; $i++) {
                $outputs[$i] = new TransactionOutput($math->getBinaryMath()->getTwosComplement(-1, 64), new Script());
            }

            $copy->setOutputs(new TransactionOutputCollection($outputs));

            // Let the others update at will. Set sequence of inputs we're not signing to 0.
            $inputCount = count($inputs);
            for ($i = 0; $i < $inputCount; $i++) {
                if ($math->cmp($i, $inputToSign) != 0) {
                    $inputs->getInput($i)->setSequence(0);
                }
            }
        }

        // This can happen regardless of whether it's ALL, NONE, or SINGLE
        if ($math->bitwiseAnd($sighashType, SignatureHashInterface::SIGHASH_ANYONECANPAY)) {
            $input = $inputs->getInput($inputToSign);
            $copy->setInputs(new TransactionInputCollection([$input]));
        }

        // Serialize the TxCopy and append the 4 byte hashtype (little endian);
        $txParser = new Parser($copy->getBuffer());
        $txParser->writeInt(4, $sighashType, true);

        return Hash::sha256d($txParser->getBuffer());
    }
}
