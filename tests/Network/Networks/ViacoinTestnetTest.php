<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Network\Networks;

use BitWasp\Bitcoin\Network\Networks\ViacoinTestnet;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ViacoinTestnetTest extends AbstractTestCase
{
    public function testViacoinTestnetNetwork()
    {
        $network = new ViacoinTestnet();
        $this->assertEquals('7f', $network->getAddressByte());
        $this->assertEquals('c4', $network->getP2shByte());
        $this->assertEquals('ff', $network->getPrivByte());
        $this->assertEquals('04358394', $network->getHDPrivByte());
        $this->assertEquals('043587cf', $network->getHDPubByte());
        $this->assertEquals('92efc5a9', $network->getNetMagicBytes());
        $this->assertEquals('tvia', $network->getSegwitBech32Prefix());
        $this->assertEquals("Viacoin Signed Message", $network->getSignedMessageMagic());
    }
}
