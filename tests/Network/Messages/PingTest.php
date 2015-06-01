<?php

namespace BitWasp\Bitcoin\Test\Network\Messages;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Network\Messages\Ping;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class PingTest extends AbstractTestCase
{
    /**
     * @return array
     */
    public function generateSet()
    {
        $set = [];
        for ($i = 0; $i < 2; $i++) {
            $set[] = [new Ping()];
        }
        return $set;
    }

    /**
     * @dataProvider generateSet
     */
    public function testPing(Ping $ping)
    {
        $this->assertInternalType('string', $ping->getNonce());
        $this->assertEquals('ping', $ping->getNetworkCommand());
        $math = $this->safeMath();
        $this->assertEquals(str_pad($math->decHex($ping->getNonce()), 16, '0', STR_PAD_LEFT), $ping->getHex());
    }
}
