<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 26/03/15
 * Time: 05:17
 */

namespace BitWasp\Bitcoin\Test\Network\Messages;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Bitcoin\Serializer\Network\NetworkMessageSerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\Messages\Version;
use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class VersionTest extends AbstractTestCase
{
    public function testVersion()
    {
        $factory = new MessageFactory(Bitcoin::getDefaultNetwork(), new Random());
        $v = '60002';
        $services = Buffer::hex('0000000000000001');
        $time = (string)time();
        $recipient = new NetworkAddress(Buffer::hex('1'), '10.0.0.1', '8332');
        $sender = new NetworkAddress(Buffer::hex('1'), '10.0.0.2', '8332');
        $userAgent = new Buffer("/Satoshi:0.7.2/");
        $lastBlock = '212672';

        $version = $factory->version(
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
        $userAgent = new Buffer("/Satoshi:0.7.2/");
        $lastBlock = '212672';
        $random = new Random();
        $nonce = $random->bytes(8)->getInt();
        new Version(
            $v,
            $services,
            $time,
            $recipient,
            $sender,
            $nonce,
            $userAgent,
            $lastBlock,
            1
        );
    }

    public function testNetworkSerializer()
    {
        $v = '60002';
        $services = Buffer::hex('0000000000000001');
        $time = (string)time();
        $recipient = new NetworkAddress(Buffer::hex('0000000000000001'), '10.0.0.1', '8332');
        $sender = new NetworkAddress(Buffer::hex('0000000000000001'), '10.0.0.2', '8332');
        $userAgent = new Buffer("/Satoshi:0.7.2/");
        $lastBlock = '212672';
        $random = new Random();
        $nonce = $random->bytes(8)->getInt();
        $version = new Version(
            $v,
            $services,
            $time,
            $recipient,
            $sender,
            $nonce,
            $userAgent,
            $lastBlock,
            true
        );

        $net = Bitcoin::getDefaultNetwork();
        $serializer = new NetworkMessageSerializer($net);
        $serialized = $version->getNetworkMessage()->getBuffer();
        $parsed = $serializer->parse($serialized)->getPayload();

        $this->assertEquals($version, $parsed);
    }
}
