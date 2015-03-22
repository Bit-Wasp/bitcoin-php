<?php

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\Script\ScriptInterface;

interface SignatureHashInterface
{
    /**
     * Default procedure: Sign ALL of the outputs.
     */
    const SIGHASH_ALL = 0x1;

    /**
     * Sign NONE of the outputs, I don't care where the bitcoins go.
     */
    const SIGHASH_NONE = 0x2;

    /**
     * Sign ONE of the outputs, I don't care where the others go.
     */
    const SIGHASH_SINGLE = 0x3;

    /**
     * Let other people add inputs to this transaction paying X. I don't
     * care who else pays.
     */
    const SIGHASH_ANYONECANPAY = 0x80;

    /**
     * Calculate the hash of the current transaction, when you are looking to
     * spend $txOut, and are signing $inputToSign. The SigHashType defaults to
     * SIGHASH_ALL, though SIGHASH_SINGLE, SIGHASH_NONE, SIGHASH_ANYONECANPAY
     * can be used.
     *
     * @param ScriptInterface $txOutScript
     * @param $inputToSign
     * @param int $sighashType
     * @return \BitWasp\Bitcoin\Buffer
     * @internal param $transaction
     */
    public function calculate(ScriptInterface $txOutScript, $inputToSign, $sighashType = SignatureHashInterface::SIGHASH_ALL);
}
