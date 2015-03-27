<?php

namespace BitWasp\Bitcoin\Test\Network\Messages;


use BitWasp\Bitcoin\Network\Messages\GetAddr;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class GetAddrTest extends AbstractTestCase
{
    public function testGetAddr()
    {
        $getaddr = new GetAddr();
        $this->assertSame('getaddr', $getaddr->getNetworkCommand());
    }
}