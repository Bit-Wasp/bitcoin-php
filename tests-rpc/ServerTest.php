<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\RpcTest;

class ServerTest extends AbstractTestCase
{
    /**
     * @var RegtestBitcoinFactory
     */
    private $rpcFactory;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        static $rpcFactory = null;
        if (null === $rpcFactory) {
            $rpcFactory = new RegtestBitcoinFactory();
        }
        $this->rpcFactory = $rpcFactory;
    }

    /**
     * Check tests are being run against regtest
     */
    public function testIfRegtest()
    {
        $server = $this->rpcFactory->startBitcoind();

        $result = $server->makeRpcRequest("getblockchaininfo");
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('chain', $result['result']);
        $this->assertEquals('regtest', $result['result']['chain']);

        $server->destroy();
    }

    public function testStartStop()
    {
        $bitcoind = $this->rpcFactory->startBitcoind();

        // First bitcoind, generate block
        $result = $bitcoind->request("generate", [1]);
        $this->assertInternalType("array", $result['result']);
        $this->assertEquals(64, strlen($result['result'][0]));

        // First bitcoind, get block height - 1
        $info = $bitcoind->request("getblockchaininfo");
        $this->assertInternalType("array", $info['result']);
        $this->assertEquals(1, $info['result']['blocks']);

        // Destroy that instance
        $bitcoind->destroy();
        $this->assertFalse($bitcoind->isRunning());

        // new bitcoind, 0 blocks
        $bitcoind = $this->rpcFactory->startBitcoind();

        $info = $bitcoind->request("getblockchaininfo");
        $this->assertInternalType("array", $info['result']);
        $this->assertEquals(0, $info['result']['blocks']);

        $bitcoind->destroy();
    }
}
