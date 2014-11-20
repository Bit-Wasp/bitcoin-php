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

    /**
     * Get limit of script size in bytes
     * @return mixed
     */
    public function getMaxBytes();

    /**
     * Get limit of bytes which can be in a single push operation
     *
     * @return mixed
     */
    public function getMaxPushBytes();

    /**
     * Get maximum limit of opcodes which can be in a script
     *
     * @return mixed
     */
    public function getMaxOpCodes();

    /**
     * @return mixed
     */
    public function checkDisabledOpcodes();

    /**
     * @param TransactionInterface $transaction
     * @param $index
     * @param $sighash_type
     * @return mixed
     */
    public function run(TransactionInterface $transaction, $index, $sighash_type);
} 