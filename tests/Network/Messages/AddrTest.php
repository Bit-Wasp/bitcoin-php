<?php

namespace BitWasp\Bitcoin\Test\Network\Messages;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\Messages\Addr;
use BitWasp\Bitcoin\Network\Structure\NetworkAddressTimestamp;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class AddrTest extends AbstractTestCase
{
    public function testAddr()
    {
        $addr = new Addr();
        $this->assertEquals(0, count($addr));
        $this->assertEquals('addr', $addr->getNetworkCommand());

        $empty = $addr->getAddresses();
        $this->assertInternalType('array', $empty);
        $this->assertEquals(0, count($empty));

        $netAddr1 = new NetworkAddressTimestamp(time(), new Buffer('0000000000000001'), '10.0.0.1', '8333');
        $netAddr2 = new NetworkAddressTimestamp(time(), new Buffer('0000000000000001'), '10.0.0.1', '8333');

        $addr->addAddress($netAddr1);
        $addr->addAddress($netAddr2);
        $this->assertEquals(2, count($addr));
        $this->assertEquals($netAddr1, $addr->getAddress(0));
        $this->assertEquals($netAddr2, $addr->getAddress(1));
    }

    public function testAddrWithArray()
    {
        $arr = array(
            new NetworkAddressTimestamp(time(), new Buffer('0000000000000001'), '10.0.0.1', '8333'),
            new NetworkAddressTimestamp(time(), new Buffer('0000000000000001'), '10.0.0.1', '8333')
        );

        $addr = new Addr($arr);
        $this->assertEquals($arr, $addr->getAddresses());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetAddressFailure()
    {
        $addr = new Addr();
        $addr->getAddress(10);
    }
}
