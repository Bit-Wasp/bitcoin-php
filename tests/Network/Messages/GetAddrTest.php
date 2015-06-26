<?php

namespace BitWasp\Bitcoin\Test\Network\Messages;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Bitcoin\Serializer\Network\NetworkMessageSerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\Messages\GetAddr;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class GetAddrTest extends AbstractTestCase
{
    public function testGetAddr()
    {
        $factory = new MessageFactory(Bitcoin::getDefaultNetwork(), new Random());
        $getaddr = $factory->getaddr();
        $this->assertSame('getaddr', $getaddr->getNetworkCommand());
        $this->assertEquals(new Buffer(), $getaddr->getBuffer());
    }

    public function testNetworkSerializer()
    {
        $parser = new NetworkMessageSerializer(Bitcoin::getDefaultNetwork());
        $getaddr = new GetAddr();
        $serialized = $getaddr->getNetworkMessage()->getBuffer();
        $parsed = $parser->parse($serialized)->getPayload();

        $this->assertEquals($getaddr, $parsed);
    }
}
