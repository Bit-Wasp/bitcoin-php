<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\SerializableInterface;

interface TransactionOutputInterface extends SerializableInterface
{
    /**
     * Get the value of this output
     * @return int|string
     */
    public function getValue();

    /**
     * Get the script for this output
     *
     * @return ScriptInterface
     */
    public function getScript();
}
