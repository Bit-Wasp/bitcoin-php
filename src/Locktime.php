<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin;

class Locktime
{
    const INT_MAX = 0xffffffff;

    /**
     * Maximum block height that can be used in locktime, as beyond
     * this is reserved for timestamp locktimes
     */
    const BLOCK_MAX = 500000000;

    /**
     * Maximum timestamp that can be encoded in locktime
     * (TIME_MAX + BLOCK_MAX = INT_MAX)
     */
    const TIME_MAX = self::INT_MAX - self::BLOCK_MAX;

    /**
     * @param int $nLockTime
     * @return bool
     */
    public function isLockedToBlock(int $nLockTime): bool
    {
        return $nLockTime > 0 && $nLockTime <= self::BLOCK_MAX;
    }

    /**
     * Convert a $timestamp to a locktime.
     * Max timestamp is 3794967296 - 04/04/2090 @ 5:34am (UTC)
     *
     * @param int $timestamp
     * @return int
     * @throws \Exception
     */
    public function fromTimestamp(int $timestamp): int
    {
        if ($timestamp > self::TIME_MAX) {
            throw new \Exception('Timestamp out of range');
        }

        $locktime = self::BLOCK_MAX + $timestamp;
        return $locktime;
    }

    /**
     * Convert a lock time to the timestamp it's locked to.
     * Throws an exception when:
     *  - Lock time appears to be in the block locktime range ( < Locktime::BLOCK_MAX )
     *  - When the lock time exceeds the max possible lock time ( > Locktime::INT_MAX )
     *
     * @param int $lockTime
     * @return int
     * @throws \Exception
     */
    public function toTimestamp(int $lockTime): int
    {
        if ($lockTime <= self::BLOCK_MAX) {
            throw new \Exception('Lock time out of range for timestamp');
        }

        if ($lockTime > self::INT_MAX) {
            throw new \Exception('Lock time too large');
        }

        $timestamp = $lockTime - self::BLOCK_MAX;
        return $timestamp;
    }

    /**
     * Convert $blockHeight to lock time. Doesn't convert anything really,
     * but does check the bounds of the given block height.
     *
     * @param int $blockHeight
     * @return int
     * @throws \Exception
     */
    public function fromBlockHeight(int $blockHeight): int
    {
        if ($blockHeight > self::BLOCK_MAX) {
            throw new \Exception('This block height is too high');
        }

        return $blockHeight;
    }

    /**
     * Convert locktime to block height tx is locked to. Doesn't convert anything
     * really, but does check the bounds of the supplied locktime.
     *
     * @param int $lockTime
     * @return int
     * @throws \Exception
     */
    public function toBlockHeight(int $lockTime): int
    {
        if ($lockTime >= self::BLOCK_MAX) {
            throw new \Exception('This locktime is out of range for a block height');
        }

        return $lockTime;
    }
}
