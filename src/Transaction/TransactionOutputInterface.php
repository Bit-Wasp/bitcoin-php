<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\SerializableInterface;

interface TransactionOutputInterface extends SerializableInterface
{
    /**
     * Get the script for this transaction
     *
     * @return mixed
     */
    public function getScript();

    /**
     * Get the value of this output
     * @return mixed
     */
    public function getValue();
}
