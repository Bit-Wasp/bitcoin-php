<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;

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
     * @param integer|string $sequence
     * @return $this
     */
    public function setSequence($sequence);

    /**
     * Get the script in this transaction
     *
     * @return Script
     */
    public function getScript();

    /**
     * @param ScriptInterface $script
     * @return $this
     */
    public function setScript(ScriptInterface $script);

    /**
     * Check whether the txid is for a coinbase transaction
     *
     * @return bool
     */
    public function isCoinBase();
}
