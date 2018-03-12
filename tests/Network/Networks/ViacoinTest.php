<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Network\Networks;

use BitWasp\Bitcoin\Network\Networks\Viacoin;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ViacoinTest extends AbstractTestCase
{
    public function testViacoin()
    {
        $network = new Viacoin();
        $this->assertEquals('47', $network->getAddressByte());
        $this->assertEquals('21', $network->getP2shByte());
        $this->assertEquals('c7', $network->getPrivByte());
        $this->assertEquals('0488ade4', $network->getHDPrivByte());
        $this->assertEquals('0488b21e', $network->getHDPubByte());
        $this->assertEquals('cbc6680f', $network->getNetMagicBytes());
        $this->assertEquals('via', $network->getSegwitBech32Prefix());
        $this->assertEquals("Viacoin Signed Message", $network->getSignedMessageMagic());
    }
}
