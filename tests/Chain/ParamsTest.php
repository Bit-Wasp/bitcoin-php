<?php

namespace BitWasp\Bitcoin\Tests\Chain;

use BitWasp\Bitcoin\Chain\Params;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ParamsTest extends AbstractTestCase
{
    public function testParams()
    {
        $params = new Params();
        $this->assertEquals(486604799, $params->powBitsLimit());
        $this->assertEquals('26959946667150639794667015087019630673637144422540572481103610249215', $params->powTargetLimit());
        $this->assertEquals(2016, $params->powRetargetInterval());
        $this->assertEquals(1209600, $params->powTargetTimespan());
        $this->assertEquals(600, $params->powTargetSpacing());

        $this->assertEquals(210000, $params->subsidyHalvingInterval());
        $this->assertEquals(120, $params->coinbaseMaturityAge());

        $this->assertEquals(750, $params->majorityEnforceBlockUpgrade());
        $this->assertEquals(1000, $params->majorityWindow());

        $this->assertEquals(1000000, $params->maxBlockSizeBytes());
        $this->assertEquals(21000000, $params->maxMoney());

    }
}
