<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 13:37
 */

namespace Bitcoin;


interface ScriptInterpreterInterface
{

    const SIGHASH_ALL          = 0x1;
    const SIGHASH_NONE         = 0x2;
    const SIGHASH_SINGLE       = 0x3;
    const SIGHASH_ANYONECANPAY = 0x80;

    public function getMaxBytes();
    public function getMaxPushBytes();
    public function getMaxOpCodes();
    public function checkDisabledOpcodes();
    public function run(TransactionInterface $transaction, $index, $sighash_type);
} 