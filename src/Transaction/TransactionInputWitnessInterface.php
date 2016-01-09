<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\BufferInterface;

interface TransactionInputWitnessInterface
{
    /**
     * @return ScriptInterface
     */
    public function getScript();

    /**
     * @return BufferInterface[]
     */
    public function getStack();

    /**
     * @return bool
     */
    public function isNull();
}
