<?php

namespace BitWasp\Bitcoin\Chain;

interface ParamsInterface
{
    /**
     * @return int
     */
    public function maxBlockSizeBytes();

    /**
     * @return int
     */
    public function subsidyHalvingInterval();

    /**
     * @return int
     */
    public function coinbaseMaturityAge();

    /**
     * @return int
     */
    public function maxMoney();

    /**
     * @return int
     */
    public function powTargetTimespan();

    /**
     * @return int
     */
    public function powTargetSpacing();

    /**
     * @return int
     */
    public function powRetargetInterval();

    /**
     * @return string
     */
    public function powTargetLimit();

    /**
     * @return string
     */
    public function powBitsLimit();


    /**
     * @return int
     */
    public function majorityEnforceBlockUpgrade();

    /**
     * @return int
     */
    public function majorityWindow();
}
