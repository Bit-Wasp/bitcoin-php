<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Bitcoin\Block\BlockInterface;

interface ParamsInterface
{
    /**
     * @return BlockHeaderInterface
     */
    public function getGenesisBlockHeader();

    /**
     * @return BlockInterface
     */
    public function getGenesisBlock();

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
     * @return int|string
     */
    public function powTargetLimit();

    /**
     * @return int
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

    /**
     * @return int
     */
    public function p2shActivateTime();

    /**
     * @return int|string
     */
    public function getMaxBlockSigOps();

    /**
     * @return int|string
     */
    public function getMaxTxSigOps();
}
