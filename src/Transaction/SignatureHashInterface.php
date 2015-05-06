<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\ScriptInterface;

interface SignatureHashInterface
{
    /**
     * Default procedure: Sign ALL of the outputs.
     */
    const SIGHASH_ALL = 1;

    /**
     * Sign NONE of the outputs, I don't care where the bitcoins go.
     */
    const SIGHASH_NONE = 2;

    /**
     * Sign ONE of the outputs, I don't care where the others go.
     */
    const SIGHASH_SINGLE = 3;

    /**
     * Let other people add inputs to this transaction paying X. I don't
     * care who else pays. (can be used with other sighash flags)
     */
    const SIGHASH_ANYONECANPAY = 128;

    /**
     * Calculate the hash of the current transaction, when you are looking to
     * spend $txOut, and are signing $inputToSign. The SigHashType defaults to
     * SIGHASH_ALL, though SIGHASH_SINGLE, SIGHASH_NONE, SIGHASH_ANYONECANPAY
     * can be used.
     *
     * @param ScriptInterface $txOutScript
     * @param $inputToSign
     * @param int $sighashType
     * @return \BitWasp\Buffertools\Buffer
     * @internal param $transaction
     */
    public function calculate(ScriptInterface $txOutScript, $inputToSign, $sighashType = SignatureHashInterface::SIGHASH_ALL);
}
