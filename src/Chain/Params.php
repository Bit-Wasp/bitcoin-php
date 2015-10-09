<?php

namespace BitWasp\Bitcoin\Chain;

class Params implements ParamsInterface
{
    /**
     * @var int
     */
    static $maxBlockSizeBytes = 1000000;

    /**
     * @var int
     */
    static $maxMoney = 21000000;

    /**
     * @var int
     */
    static $subsidyHalvingInterval = 210000;

    /**
     * @var int
     */
    static $coinbaseMaturityAge = 120;

    /**
     * = 14 * 24 * 60 * 60
     * @var int
     */
    static $powTargetTimespan = 1209600;

    /**
     * = 10 * 60
     * @var int
     */
    static $powTargetSpacing = 600;

    /**
     * @var int
     */
    static $powRetargetInterval = 2016;

    /**
     * @var string
     */
    static $powTargetLimit = '26959946667150639794667015087019630673637144422540572481103610249215';

    /**
     * Hex: 1d00ffff
     * @var string
     */
    static $powBitsLimit = '486604799';

    /**
     * @var int
     */
    static $majorityWindow = 1000;

    /**
     * @var int
     */
    static $majorityEnforceBlockUpgrade = 750;

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
