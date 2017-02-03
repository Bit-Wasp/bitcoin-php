<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\SerializableInterface;

interface TransactionInputInterface extends SerializableInterface
{
    /**
     * The default sequence.
     */
    const SEQUENCE_FINAL = 0xffffffff;

    /* If this flag set, CTxIn::nSequence is NOT interpreted as a
     * relative lock-time. */
    const SEQUENCE_LOCKTIME_DISABLE_FLAG = 1 << 31; // 1 << 31

    /* If CTxIn::nSequence encodes a relative lock-time and this flag
     * is set, the relative lock-time has units of 512 seconds,
     * otherwise it specifies blocks with a granularity of 1. */
    const SEQUENCE_LOCKTIME_TYPE_FLAG = 1 << 22; // 1 << 22;

    /* If CTxIn::nSequence encodes a relative lock-time, this mask is
     * applied to extract that lock-time from the sequence field. */
    const SEQUENCE_LOCKTIME_MASK = 0x0000ffff;

    /**
     * Get the outpoint
     *
     * @return OutPointInterface
     */
    public function getOutPoint();

    /**
     * Get the scriptSig for this input
     *
     * @return ScriptInterface
     */
    public function getScript();

    /**
     * Get the sequence number for this input
     *
     * @return int
     */
    public function getSequence();

    /**
     * Equality check with $this and $other
     *
     * @param TransactionInputInterface $other
     * @return bool
     */
    public function equals(TransactionInputInterface $other);

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
     * Checks whether the SEQUENCE_LOCKTIME_DISABLE_FLAG is set
     * Always returns true if txin is coinbase.
     *
     * @return int
     */
    public function isSequenceLockDisabled();

    /**
     * Indicates whether the input is locked with a time based lock (as opposed to block)
     *
     * @return bool
     */
    public function isLockedToTime();

    /**
     * Returns whether the input is locked with a block based lock (as opposed to time)
     *
     * @return bool
     */
    public function isLockedToBlock();

    /**
     * Returns the relative block time for the input.
     * Range limited to 0 - 33553920 (approx 1 yr)
     * @return int
     */
    public function getRelativeBlockLock();

    /**
     * Returns the relative locktime for the input in seconds.
     * Range limited to 0 - 65535
     *
     * @return int
     */
    public function getRelativeTimeLock();
}
