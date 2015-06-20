<?php

namespace BitWasp\Bitcoin\Test\Network\Messages;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\Messages\Version;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Bitcoin\Serializer\Network\NetworkMessageSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;

class MessageTest extends AbstractTestCase
{
    public function getMockPayload($command)
    {
        $mock = $this->getMock('BitWasp\Bitcoin\Network\NetworkSerializable');
        $mock->expects($this->any())
            ->method('getNetworkCommand')
            ->willReturn($command);
        $mock->expects($this->atLeastOnce())
            ->method('getBuffer')
            ->willReturn(new Buffer());
        return $mock;
    }

    public function getMockMessage($command, $invalidChecksum = false)
    {
        $payload = $this->getMockPayload($command);
        $net = Bitcoin::getDefaultNetwork();

        $msg = $this->getMock('BitWasp\Bitcoin\Network\NetworkMessage', [
            'getCommand', 'getPayload', 'getChecksum'
        ], [
            $net,
            $payload
        ]);
        $msg->expects($this->atLeastOnce())
            ->method('getCommand')
            ->willReturn($command);
        $msg->expects($this->atLeastOnce())
            ->method('getPayload')
            ->willReturn($payload);

        if ($invalidChecksum) {
            $random = new Random();
            $bytes = $random->bytes(4);
            $msg->expects($this->atLeastOnce())
                ->method('getChecksum')
                ->willReturn($bytes);
        } else {
            $msg->expects($this->atLeastOnce())
                ->method('getChecksum')
                ->willReturn(Hash::sha256d(new Buffer())->slice(0, 4));
        }

        return $msg;
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid command
     */
    public function testInvalidCommand()
    {
        $invalid = $this->getMockMessage('invalid');
        $serialized = $invalid->getBuffer();

        $serializer = new NetworkMessageSerializer(Bitcoin::getDefaultNetwork());
        $serializer->parse($serialized);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid packet checksum
     */
    public function testInvalidChecksum()
    {
        $v = '60002';
        $services = Buffer::hex('0000000000000001');
        $time = '123456789';
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

        $msg = $version->getNetworkMessage();
        $realBuffer = $msg->getBuffer();

        $invalid = Buffertools::concat(
            Buffertools::concat(
                $realBuffer->slice(0, 20),
                Buffer::hex('00000000')
            ),
            $realBuffer->slice(24)
        );
        $serializer = new NetworkMessageSerializer(Bitcoin::getDefaultNetwork());
        $serializer->parse($invalid);
    }

    /**
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     * @expectedException \RuntimeException
     * @expectedExceptionMessage
     */
    public function testInvalidBytes()
    {
        $v = '60002';
        $services = Buffer::hex('0000000000000001');
        $time = '123456789';
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

        $bitcoin = new NetworkMessageSerializer(NetworkFactory::bitcoin());

        $serialized = $version->getNetworkMessage(NetworkFactory::viacoinTestnet())->getBuffer();
        $bitcoin->parse($serialized);
    }
}
