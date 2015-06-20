<?php

namespace BitWasp\Bitcoin\Test\Network\Messages;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Bitcoin\Serializer\Network\NetworkMessageSerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\Messages\MemPool;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class MemPoolTest extends AbstractTestCase
{
    public function testMemPool()
    {
        $mem = new MemPool();
        $this->assertSame('mempool', $mem->getNetworkCommand());
        $this->assertEquals(new Buffer(), $mem->getBuffer());
    }

    public function testNetworkSerializer()
    {
        $mem = new MemPool();
        $serializer = new NetworkMessageSerializer(Bitcoin::getDefaultNetwork());

        $parsed = $serializer->parse($mem->getNetworkMessage()->getBuffer())->getPayload();
        $this->assertEquals($mem, $parsed);
    }
}
