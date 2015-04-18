<?php

namespace BitWasp\Bitcoin\Test\Network\Messages;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\Messages\GetAddr;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class GetAddrTest extends AbstractTestCase
{
    public function testGetAddr()
    {
        $getaddr = new GetAddr();
        $this->assertSame('getaddr', $getaddr->getNetworkCommand());
        $this->assertEquals(new Buffer(), $getaddr->getBuffer());
    }
}
