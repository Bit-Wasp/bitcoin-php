<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Network\Networks;

use BitWasp\Bitcoin\Network\Networks\LitecoinTestnet;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class LitecoinTestnetTest extends AbstractTestCase
{
    public function testLitecoinTestnetNetwork()
    {
        $network = new LitecoinTestnet();
        $this->assertEquals('6f', $network->getAddressByte());
        $this->assertEquals('3a', $network->getP2shByte());
        $this->assertEquals('ef', $network->getPrivByte());
        $this->assertEquals('04358394', $network->getHDPrivByte());
        $this->assertEquals('043587cf', $network->getHDPubByte());
        $this->assertEquals('f1c8d2fd', $network->getNetMagicBytes());
        $this->assertEquals('tltc', $network->getSegwitBech32Prefix());
        $this->assertEquals("Litecoin Signed Message", $network->getSignedMessageMagic());
    }
}
