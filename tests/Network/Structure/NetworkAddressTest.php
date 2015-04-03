<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 26/03/15
 * Time: 05:17
 */

namespace BitWasp\Bitcoin\Test\Network\Structure;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Bitcoin\Network\Structure\NetworkAddressTimestamp;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class NetworkAddressTest extends AbstractTestCase
{
    /**
     * @return array
     */
    public function getVectors()
    {
        $port = 8333;
        return [
            ["10.0.0.1", $port,  "0100000000000000"."00000000000000000000ffff0a000001208d"],
            ["127.0.0.1", $port, "0100000000000000"."00000000000000000000ffff7f000001208d"]
        ];
    }

    /**
     * @dataProvider getVectors
     */
    public function testNetworkAddress($ip, $port, $expected)
    {
        $services = Buffer::hex('0000000000000001');
        $from = new NetworkAddress($services, $ip, $port);
        $this->assertEquals($services, $from->getServices());
        $this->assertEquals($ip, $from->getIp());
        $this->assertEquals($port, $from->getPort());
        $this->assertEquals($expected, $from->getBuffer()->getHex());
    }

    public function testNetworkAddressTimestamp()
    {
        $ip = '127.0.0.1';
        $port = 8333;
        $time = time();
        $services = Buffer::hex('0000000000000001');
        $from = new NetworkAddressTimestamp($time, $services, $ip, $port);
        $this->assertEquals($time, $from->getTimestamp());
        $this->assertEquals($services, $from->getServices());
        $this->assertEquals($ip, $from->getIp());
        $this->assertEquals($port, $from->getPort());

    }
}
