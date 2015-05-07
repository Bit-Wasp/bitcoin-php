<?php

namespace BitWasp\Bitcoin\Tests\Rpc;


use BitWasp\Bitcoin\Rpc\RpcFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class RpcFactoryTest extends AbstractTestCase
{
    public function testBitcoind()
    {
        $bitcoind = RpcFactory::bitcoind('127.0.0.1', 8332, 'user', 'password');
        $this->assertInstanceOf('BitWasp\Bitcoin\Rpc\Client\Bitcoind', $bitcoind);
    }
}