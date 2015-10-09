<?php

namespace BitWasp\Bitcoin\Chain;

class Params implements ParamsInterface
{
    /**
     * @var int
     */
    protected static $maxBlockSizeBytes = 1000000;

    /**
     * @var int
     */
    protected static $maxMoney = 21000000;

    /**
     * @var int
     */
    protected static $subsidyHalvingInterval = 210000;

    /**
     * @var int
     */
    protected static $coinbaseMaturityAge = 120;

    /**
     * = 14 * 24 * 60 * 60
     * @var int
     */
    protected static $powTargetTimespan = 1209600;

    /**
     * = 10 * 60
     * @var int
     */
    protected static $powTargetSpacing = 600;

    /**
     * @var int
     */
    protected static $powRetargetInterval = 2016;

    /**
     * @var string
     */
    protected static $powTargetLimit = '26959946667150639794667015087019630673637144422540572481103610249215';

    /**
     * Hex: 1d00ffff
     * @var string
     */
    protected static $powBitsLimit = 486604799;

    /**
     * @var int
     */
    protected static $majorityWindow = 1000;

    /**
     * @var int
     */
    protected static $majorityEnforceBlockUpgrade = 750;

    /**
     * @return int
     */
    public function maxBlockSizeBytes()
    {
        return static::$maxBlockSizeBytes;
    }

    /**
     * @return int
     */
    public function subsidyHalvingInterval()
    {
        return static::$subsidyHalvingInterval;
    }

    /**
     * @return int
     */
    public function coinbaseMaturityAge()
    {
        return static::$coinbaseMaturityAge;
    }

    /**
     * @return int
     */
    public function maxMoney()
    {
        return static::$maxMoney;
    }

    /**
     * @return int
     */
    public function powTargetTimespan()
    {
        return static::$powTargetTimespan ;
    }

    /**
     * @return int
     */
    public function powTargetSpacing()
    {
        return static::$powTargetSpacing;
    }

    /**
     * @return int
     */
    public function powRetargetInterval()
    {
        return static::$powRetargetInterval;
    }

    /**
     * @return string
     */
    public function powTargetLimit()
    {
        return static::$powTargetLimit;
    }

    /**
     * @return string
     */
    public function powBitsLimit()
    {
        return static::$powBitsLimit;
    }

    /**
     * @return int
     */
    public function majorityEnforceBlockUpgrade()
    {
        return static::$majorityEnforceBlockUpgrade;
    }

    /**
     * @return int
     */
    public function majorityWindow()
    {
        return static::$majorityWindow;
    }
}
