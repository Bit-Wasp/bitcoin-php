<?php

namespace Afk11\Bitcoin\Transaction;

use Afk11\Bitcoin\Script\Script;
use Afk11\Bitcoin\Script\ScriptInterface;

interface TransactionInputInterface
{
    /**
     * The default sequence.
     */
    const DEFAULT_SEQUENCE = 0xffffffff;

    /**
     * Return the txid for the transaction being spent
     * @return string
     */
    public function getTransactionId();

    /**
     * Return the vout for the transaction being spent
     *
     * @return int
     */
    public function getVout();

    /**
     * Set the sequence number for this transaction.
     *
     * @return int
     */
    public function getSequence();

    /**
     * Get the script in this transaction
     *
     * @return Script
     */
    public function getScript();

    /**
     * @param ScriptInterface $script
     * @return mixed
     */
    public function setScript(ScriptInterface $script);

    /**
     * @return TransactionOutputInterface
     */
    public function getPrevOutput();

    /**
     * Check whether the txid is for a coinbase transaction
     *
     * @return bool
     */
    public function isCoinBase();
}
