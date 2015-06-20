<?php

namespace BitWasp\Bitcoin\Test\Network\Messages;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Bitcoin\Network\Messages\VerAck;
use BitWasp\Bitcoin\Serializer\Network\NetworkMessageSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class VerAckTest extends AbstractTestCase
{
    public function test()
    {
        $network = Bitcoin::getNetwork();
        $verack = new VerAck();
        $expected = 'f9beb4d976657261636b000000000000000000005df6e0e2';

        $this->assertEquals($expected, $verack->getNetworkMessage($network)->getHex());
        $this->assertSame('verack', $verack->getNetworkCommand());
    }

    public function testNetworkSerializer()
    {
        $net = Bitcoin::getDefaultNetwork();
        $serializer = new NetworkMessageSerializer($net);
        $factory = new MessageFactory($net, new Random());
        $verack = $factory->verack();

        $serialized = $verack->getNetworkMessage()->getBuffer();
        $parsed = $serializer->parse($serialized)->getPayload();

        $this->assertEquals($verack, $parsed);

    }
}
