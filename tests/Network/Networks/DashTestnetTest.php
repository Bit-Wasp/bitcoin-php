<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Network\Networks;

use BitWasp\Bitcoin\Network\Networks\DashTestnet;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class DashTestnetTest extends AbstractTestCase
{
    public function testDashTestnetNetwork()
    {
        $network = new DashTestnet();
        $this->assertEquals('8b', $network->getAddressByte());
        $this->assertEquals('13', $network->getP2shByte());
        $this->assertEquals('ef', $network->getPrivByte());
        $this->assertEquals('04358394', $network->getHDPrivByte());
        $this->assertEquals('043587cf', $network->getHDPubByte());
        $this->assertEquals('ffcae2ce', $network->getNetMagicBytes());
        $this->assertEquals("DarkCoin Signed Message", $network->getSignedMessageMagic());
    }
}
