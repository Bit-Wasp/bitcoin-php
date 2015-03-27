<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 27/03/15
 * Time: 01:20
 */

namespace BitWasp\Bitcoin\Test\Network\Messages;


use BitWasp\Bitcoin\Network\Messages\MemPool;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class MemPoolTest extends AbstractTestCase
{
    public function testMemPool()
    {
        $mem = new MemPool();
        $this->assertSame('mempool', $mem->getNetworkCommand());
    }
}