<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Math\Math;

class Locktime
{
    /**
     * @var Math
     */
    private $math;

    /**
     * 0xffffffff
     */
    const INT_MAX = '4294967296';

    /**
     * Maximum block height that can be used in locktime, as beyond
     * this is reserved for timestamp locktimes
     */
    const BLOCK_MAX = '500000000';

    /**
     * Maximum timestamp that can be encoded in locktime
     * (TIME_MAX + BLOCK_MAX = INT_MAX)
     */

    const TIME_MAX = '3794967296';

    /**
     * @param Math $math
     */
    public function __construct(Math $math)
    {
        $this->math = $math;
    }

    /**
     * Convert a $timestamp to a locktime.
     * Max timestamp is 3794967296 - 04/04/2090 @ 5:34am (UTC)
     *
     * @param int|string $timestamp
     * @return int|string
     * @throws \Exception
     */
    public function fromTimestamp($timestamp)
    {
        if ($this->math->cmp($timestamp, self::TIME_MAX) > 0) {
            throw new \Exception('Timestamp out of range');
        }

        $locktime = $this->math->add(self::BLOCK_MAX, $timestamp);
        return $locktime;
    }

    /**
     * Convert a lock time to the timestamp it's locked to.
     * Throws an exception when:
     *  - Lock time appears to be in the block locktime range ( < Locktime::BLOCK_MAX )
     *  - When the lock time exceeds the max possible lock time ( > Locktime::INT_MAX )
     *
     * @param int|string $lockTime
     * @return int|string
     * @throws \Exception
     */
    public function toTimestamp($lockTime)
    {
        if ($this->math->cmp($lockTime, self::BLOCK_MAX) <= 0) {
            throw new \Exception('Lock time out of range for timestamp');
        }

        if ($this->math->cmp($lockTime, self::INT_MAX) > 0) {
            throw new \Exception('Lock time too large');
        }

        $timestamp = $this->math->sub($lockTime, self::BLOCK_MAX);
        return $timestamp;
    }

    /**
     * Convert $blockHeight to lock time. Doesn't convert anything really,
     * but does check the bounds of the given block height.
     *
     * @param int|string $blockHeight
     * @return int|string
     * @throws \Exception
     */
    public function fromBlockHeight($blockHeight)
    {
        if ($this->math->cmp($blockHeight, self::BLOCK_MAX) > 0) {
            throw new \Exception('This block height is too high');
        }

        return $blockHeight;
    }

    /**
     * Convert locktime to block height tx is locked to. Doesn't convert anything
     * really, but does check the bounds of the supplied locktime.
     *
     * @param int|string $lockTime
     * @return int|string
     * @throws \Exception
     */
    public function toBlockHeight($lockTime)
    {
        if ($this->math->cmp($lockTime, self::BLOCK_MAX) > 0) {
            throw new \Exception('This locktime is out of range for a block height');
        }

        return $lockTime;
    }
}
