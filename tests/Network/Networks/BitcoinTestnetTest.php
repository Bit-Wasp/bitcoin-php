<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Network\Networks;

use BitWasp\Bitcoin\Network\Networks\BitcoinTestnet;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class BitcoinTestnetTest extends AbstractTestCase
{
    public function testBitcoinTestnetNetwork()
    {
        $network = new BitcoinTestnet();
        $this->assertEquals('6f', $network->getAddressByte());
        $this->assertEquals('c4', $network->getP2shByte());
        $this->assertEquals('ef', $network->getPrivByte());
        $this->assertEquals('04358394', $network->getHDPrivByte());
        $this->assertEquals('043587cf', $network->getHDPubByte());
        $this->assertEquals('0709110b', $network->getNetMagicBytes());
        $this->assertEquals('tb', $network->getSegwitBech32Prefix());
        $this->assertEquals("Bitcoin Signed Message", $network->getSignedMessageMagic());
    }
}
