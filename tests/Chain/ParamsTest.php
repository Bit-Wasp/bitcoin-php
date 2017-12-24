<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Chain;

use BitWasp\Bitcoin\Chain\Params;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ParamsTest extends AbstractTestCase
{
    public function testParams()
    {
        $math = new Math();
        $params = new Params($math);
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
        $this->assertEquals(1333238400, $params->p2shActivateTime());

        $this->assertEquals(20000, $params->getMaxBlockSigOps());
        $this->assertEquals(4000, $params->getMaxTxSigOps());

        $genesis = $params->getGenesisBlock();
        $header = $genesis->getHeader();
        $hash = $header->getHash();
        $merkle = $genesis->getMerkleRoot();
        $this->assertEquals('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f', $hash->getHex());
        $this->assertEquals('4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b', $merkle->getHex());
    }
}
