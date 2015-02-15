<?php

namespace Afk11\Bitcoin\Script;

use Afk11\Bitcoin\Transaction\TransactionInterface;

interface ScriptInterpreterInterface
{

    const SCRIPT_ERR_BAD_OPCODE = "";
    const SCRIPT_ERR_PUSH_SIZE = "";
    const SCRIPT_ERR_OP_COUNT = "";
    const SCRIPT_ERR_MINIMALDATA = "";

    const SIGHASH_ALL          = 0x1;
    const SIGHASH_NONE         = 0x2;
    const SIGHASH_SINGLE       = 0x3;
    const SIGHASH_ANYONECANPAY = 0x80;

    /**
     * @param TransactionInterface $transaction
     * @param $index
     * @param $sighash_type
     * @return mixed
     */
  //  public function run(TransactionInterface $transaction, $index, $sighash_type);
}
