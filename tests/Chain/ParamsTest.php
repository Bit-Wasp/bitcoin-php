<?php

namespace BitWasp\Bitcoin\Tests\Chain;


use BitWasp\Bitcoin\Chain\Params;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ParamsTest extends AbstractTestCase
{
    public function testParams()
    {
        $params = new Params();
        $this->assertEquals(Params::$powBitsLimit, $params->powBitsLimit());
        $this->assertEquals(Params::$powTargetLimit, $params->powTargetLimit());
        $this->assertEquals(Params::$powRetargetInterval, $params->powRetargetInterval());
        $this->assertEquals(Params::$powTargetTimespan, $params->powTargetTimespan());
        $this->assertEquals(Params::$powTargetSpacing, $params->powTargetSpacing());

        $this->assertEquals(Params::$subsidyHalvingInterval, $params->subsidyHalvingInterval());
        $this->assertEquals(Params::$coinbaseMaturityAge, $params->coinbaseMaturityAge());

        $this->assertEquals(Params::$majorityEnforceBlockUpgrade, $params->majorityEnforceBlockUpgrade());
        $this->assertEquals(Params::$majorityWindow, $params->majorityWindow());

        $this->assertEquals(Params::$maxBlockSizeBytes, $params->maxBlockSizeBytes());
        $this->assertEquals(Params::$maxMoney, $params->maxMoney());


    }
}