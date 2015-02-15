<?php

namespace Afk11\Bitcoin\Transaction;

interface TransactionOutputInterface
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
