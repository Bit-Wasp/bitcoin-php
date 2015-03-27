<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 26/03/15
 * Time: 05:17
 */

namespace BitWasp\Bitcoin\Test\Network\Messages;


use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Network\Messages\Version;
use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class VersionTest extends AbstractTestCase
{
    public function testVersion()
    {
        $v = '60002';
        $services = Buffer::hex('0000000000000001');
        $time = time();
        $recipient = new NetworkAddress(Buffer::hex('1'), '10.0.0.1', '8332');
        $sender = new NetworkAddress(Buffer::hex('1'), '10.0.0.2', '8332');
        $userAgent = "/Satoshi:0.7.2/";
        $lastBlock = 212672;
        $version = new Version(
            $v,
            $services,
            $time,
            $recipient,
            $sender,
            $userAgent,
            $lastBlock,
            true
        );

        $this->assertEquals('version', $version->getNetworkCommand());
        $this->assertEquals($userAgent, $version->getUserAgent());
        $this->assertEquals($v, $version->getVersion());
        $this->assertEquals($time, $version->getTimestamp());
        $this->assertEquals($sender, $version->getSenderAddress());
        $this->assertEquals($recipient, $version->getRecipientAddress());
        $this->assertEquals($services, $version->getServices());
        $this->assertEquals($lastBlock, $version->getStartHeight());
        $this->assertInternalType('string', $version->getNonce());
        $this->assertTrue($version->getRelay());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testVersionFails()
    {
        $v = '60002';
        $services = Buffer::hex('0000000000000001');
        $time = time();
        $recipient = new NetworkAddress(Buffer::hex('1'), '10.0.0.1', '8332');
        $sender = new NetworkAddress(Buffer::hex('1'), '10.0.0.2', '8332');
        $userAgent = "/Satoshi:0.7.2/";
        $lastBlock = 212672;
        $version = new Version(
            $v,
            $services,
            $time,
            $recipient,
            $sender,
            $userAgent,
            $lastBlock,
            1
        );
    }
}
