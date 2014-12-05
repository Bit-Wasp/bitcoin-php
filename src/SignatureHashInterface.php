<?php

namespace Bitcoin;

use Bitcoin\Util\Buffer;
use Bitcoin\TransactionOutputInterface;

/**
 * Interface SigHashInterface
 * @package Bitcoin\SigHash
 */
interface SignatureHashInterface
{
    const SIGHASH_ALL           = 0x1;
    const SIGHASH_NONE          = 0x2;
    const SIGHASH_SINGLE        = 0x3;
    const SIGHASH_ANYONECANPAY  = 0x80;

    /**
     * @param $transaction
     * @param $inputToSign
     * @return Buffer
     */
    public function calculateHash(TransactionOutputInterface $txOut, $inputToSign, $sighashType = SignatureHashInterface::SIGHASH_ALL);
}