<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\ScriptInterface;

interface TransactionInputInterface extends \ArrayAccess
{
    /**
     * The default sequence.
     */
    const SEQUENCE_FINAL = 0xffffffff;

    /**
     * Check whether the transaction is the Coinbase, ie, it has
     * one input which spends the `null` outpoint
     *
     * @return bool
     */
    public function isCoinBase();

    /**
     * @return bool
     */
    public function isFinal();

    /**
     * Return the txid for the transaction being spent
     * @return string
     */
    public function getTransactionId();

    /**
     * Return the nPrevOut for the transaction being spent
     *
     * @return int
     */
    public function getVout();

    /**
     * Get the script in this transaction
     *
     * @return ScriptInterface
     */
    public function getScript();

    /**
     * Set the sequence number for this transaction.
     *
     * @return int
     */
    public function getSequence();
}
