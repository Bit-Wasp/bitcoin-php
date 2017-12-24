<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Network\Networks;

use BitWasp\Bitcoin\Network\Networks\Dash;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class DashTest extends AbstractTestCase
{
    public function testDashNetwork()
    {
        $network = new Dash();
        $this->assertEquals('4c', $network->getAddressByte());
        $this->assertEquals('10', $network->getP2shByte());
        $this->assertEquals('cc', $network->getPrivByte());
        $this->assertEquals('0488ade4', $network->getHDPrivByte());
        $this->assertEquals('0488b21e', $network->getHDPubByte());
        $this->assertEquals('bd6b0cbf', $network->getNetMagicBytes());
        $this->assertEquals("DarkCoin Signed Message", $network->getSignedMessageMagic());
    }
}
