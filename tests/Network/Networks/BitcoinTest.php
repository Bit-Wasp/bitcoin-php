<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Network\Networks;

use BitWasp\Bitcoin\Network\Networks\Bitcoin;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class BitcoinTest extends AbstractTestCase
{
    public function testBitcoinNetwork()
    {
        $network = new Bitcoin();
        $this->assertEquals('00', $network->getAddressByte());
        $this->assertEquals('05', $network->getP2shByte());
        $this->assertEquals('80', $network->getPrivByte());
        $this->assertEquals('0488ade4', $network->getHDPrivByte());
        $this->assertEquals('0488b21e', $network->getHDPubByte());
        $this->assertEquals('d9b4bef9', $network->getNetMagicBytes());
        $this->assertEquals("bc", $network->getSegwitBech32Prefix());
        $this->assertEquals("Bitcoin Signed Message", $network->getSignedMessageMagic());
    }
}
