<?php

namespace BitWasp\Bitcoin\Test\Network\Messages;

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
}
