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
     * Maximum block height that can be used in locktime, as beyond
     * this is reserved for timestamp locktimes
     */
    const BLOCK_MAX = '500000000';
    const TIME_MAX = '3794967296';
    const INT_MAX = '4294967296';

    /**
     * @param Math $math
     */
    public function __construct(Math $math)
    {
        $this->math = $math;
    }

    /**
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