<?php

namespace BitWasp\Bitcoin\Tests\Rpc\Client;

use BitWasp\Bitcoin\JsonRpc\JsonRpcClient;
use BitWasp\Bitcoin\Rpc\Client\Bitcoind;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

class BitcoindTest extends AbstractTestCase
{
    private $jsonRpcType = 'BitWasp\Bitcoin\JsonRpc\JsonRpcClient';

    public $mockGetBlockCount = 0;

    /**
     * Test totally invalid details so the JsonRpcClient returns null.
     * @expectedException \Exception
     * @expectedExceptionMessage Received no response from server
     */
    public function testBitcoind()
    {
        $json = new JsonRpcClient("127.0.0.1", 30929);
        $bitcoind = new Bitcoind($json);
        $bitcoind->getinfo();
    }

    /**
     * Test mocking a request where the JsonRpcClient returns null.
     * @expectedException \Exception
     * @expectedExceptionMessage Received no response from server
     */
    public function testBitcoindException()
    {
        $json = $this->getMock($this->jsonRpcType, ['execute'], ['127.0.0.1', 8332]);
        $json
            ->expects($this->once())
            ->method('execute')
            ->willReturn(null);

        $bitcoind = new Bitcoind($json);
        $bitcoind->getinfo();
    }

    /**
     * Test mocking get info for a successful response.
     */
    public function testMockGetInfo()
    {
        $json = $this->getMock($this->jsonRpcType, ['execute'], ['127.0.0.1', 8332]);
        $json
            ->expects($this->once())
            ->method('execute')
            ->willReturn(json_decode('{
    "version" : 109900,
    "protocolversion" : 70002,
    "walletversion" : 60000,
    "balance" : 0.05000000,
    "blocks" : 355216,
    "timeoffset" : -7,
    "connections" : 8,
    "proxy" : "",
    "difficulty" : 47643398017.80344391,
    "testnet" : false,
    "keypoololdest" : 1415230923,
    "keypoolsize" : 101,
    "paytxfee" : 0.00000000,
    "relayfee" : 0.00001000,
    "errors" : "This is a pre-release test build - use at your own risk - do not use for mining or merchant applications"
}', true));

        $bitcoind = new Bitcoind($json);
        $results = $bitcoind->getinfo();
        $this->assertInternalType('array', $results);
    }

    /**
     * Test gets a block hash
     */
    public function testMockGetBestBlockHash()
    {
        $hash = '00000000000000000212140b130a572f2cd954396e8a34685359a00863a7fcb5';
        $json = $this->getMock($this->jsonRpcType, ['execute'], ['127.0.0.1', 8332]);
        $json
            ->expects($this->once())
            ->method('execute')
            ->willReturn($hash);

        $bitcoind = new Bitcoind($json);
        $results = $bitcoind->getbestblockhash();

        $this->assertEquals($hash, $results);
        //000000007bc154e0fa7ea32218a72fe2c1bb9f86cf8c9ebf9a715ed27fdb229a
    }

    /**
     * Get a block hash should return a hash.
     */
    public function testMockGetBlockHash()
    {
        $height = 100;
        $hash = '000000007bc154e0fa7ea32218a72fe2c1bb9f86cf8c9ebf9a715ed27fdb229a';
        $json = $this->getMock($this->jsonRpcType, ['execute'], ['127.0.0.1', 8332]);
        $json
            ->expects($this->once())
            ->method('execute')
            ->willReturn($hash);

        $bitcoind = new Bitcoind($json);
        $results = $bitcoind->getblockhash($height);

        $this->assertEquals($hash, $results);
    }

    /**
     * Get a raw tx hex
     */
    public function testMockGetRawTransaction()
    {
        $tx = '0100000001d307610c0d7ba6972bc1ba213ba766814213e63f99d443f93a87025de3649ae42f0200006b483045022100cfa227c903c88df20c2798ff48d53fed4b2a9c5e25f24a0dc63aba39a778773302206fb057829c7ecb62150a306c6c08c43cbbe58591ab2dd7430afd5d7591e25687012103ef3f4f6ba23280a262aa8ffb8743df57c97c21f38de09c76fa6fa1851a2c6540ffffffff01ef240000000000001976a9145eacfaa836d1806b13e9e42730d99c6b0914dcae88ac00000000';
        $hash = '75bf3c64b181f92f0a5262e7c4ea315baa48b7dd3cf64c028a259a2c91064371';
        $json = $this->getMock($this->jsonRpcType, ['execute'], ['127.0.0.1', 8332]);
        $json
            ->expects($this->once())
            ->method('execute')
            ->willReturn($tx);

        $bitcoind = new Bitcoind($json);
        $results = $bitcoind->getrawtransaction($hash, false);

        $this->assertEquals($tx, $results);
    }

    /**
     * Get raw transaction with details - wrapper instantiates tx.
     */
    public function testMockGetRawTransactionVerbose()
    {
        $tx = '0100000001d307610c0d7ba6972bc1ba213ba766814213e63f99d443f93a87025de3649ae42f0200006b483045022100cfa227c903c88df20c2798ff48d53fed4b2a9c5e25f24a0dc63aba39a778773302206fb057829c7ecb62150a306c6c08c43cbbe58591ab2dd7430afd5d7591e25687012103ef3f4f6ba23280a262aa8ffb8743df57c97c21f38de09c76fa6fa1851a2c6540ffffffff01ef240000000000001976a9145eacfaa836d1806b13e9e42730d99c6b0914dcae88ac00000000';
        $hash = '75bf3c64b181f92f0a5262e7c4ea315baa48b7dd3cf64c028a259a2c91064371';
        $json = $this->getMock($this->jsonRpcType, ['execute'], ['127.0.0.1', 8332]);
        $json
            ->expects($this->once())
            ->method('execute')
            ->willReturn($tx);

        $bitcoind = new Bitcoind($json);
        $results = $bitcoind->getrawtransaction($hash, true);

        $this->assertInstanceof('BitWasp\Bitcoin\Transaction\Transaction', $results);
        $this->assertEquals($tx, $results->getHex());
        $this->assertEquals($hash, $results->getTransactionId());
    }

    /**
     * Mock a successful block request, which should return a Block object with
     * all the necessary Transaction instances
     */
    public function testMockGetBlock()
    {
        $rpc = json_decode('{
    "hash" : "00000000839a8e6886ab5951d76f411475428afc90947ee320161bbf18eb6048",
    "confirmations" : 355538,
    "size" : 215,
    "height" : 1,
    "version" : 1,
    "merkleroot" : "0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098",
    "tx" : [
        "0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098"
    ],
    "time" : 1231469665,
    "nonce" : 2573394689,
    "bits" : "1d00ffff",
    "difficulty" : 1.00000000,
    "chainwork" : "0000000000000000000000000000000000000000000000000000000200020002",
    "previousblockhash" : "000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f",
    "nextblockhash" : "000000006a625f06636b8bb6ac7b960a8d03705d1ace08b1a19da3fdcc99ddbd"
}', true);

        $txHex = '01000000010000000000000000000000000000000000000000000000000000000000000000ffffffff0704ffff001d0104ffffffff0100f2052a0100000043410496b538e853519c726a2c91e61ec11600ae1390813a627c66fb8be7947be63c52da7589379515d4e0a604f8141781e62294721166bf621e73a82cbf2342c858eeac00000000';

        $json = $this->getMock($this->jsonRpcType, ['execute', 'send'], ['127.0.0.1', 8332]);
        $json->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturnCallback(
                function () use ($rpc, $txHex) {
                    $re = ($this->mockGetBlockCount++ == 0)
                        ? $rpc
                        : array($txHex);

                    return $re;
                }
            );

        $json->expects($this->atLeastOnce())
            ->method('send')
            ->willReturn(array($txHex));

        $bitcoind = new Bitcoind($json);
        $hash = '00000000839a8e6886ab5951d76f411475428afc90947ee320161bbf18eb6048';
        $block = $bitcoind->getblock('00000000839a8e6886ab5951d76f411475428afc90947ee320161bbf18eb6048');

        $this->assertEquals($hash, $block->getHeader()->getBlockHash());
        $this->assertEquals($txHex, $block->getTransactions()->getTransaction(0)->getHex());
    }

    /**
     * Should take a TransactionInterface, return a hash.
     */
    public function testMockSendRawTransaction()
    {
        $tx = '0100000001d307610c0d7ba6972bc1ba213ba766814213e63f99d443f93a87025de3649ae42f0200006b483045022100cfa227c903c88df20c2798ff48d53fed4b2a9c5e25f24a0dc63aba39a778773302206fb057829c7ecb62150a306c6c08c43cbbe58591ab2dd7430afd5d7591e25687012103ef3f4f6ba23280a262aa8ffb8743df57c97c21f38de09c76fa6fa1851a2c6540ffffffff01ef240000000000001976a9145eacfaa836d1806b13e9e42730d99c6b0914dcae88ac00000000';

        $hash = '75bf3c64b181f92f0a5262e7c4ea315baa48b7dd3cf64c028a259a2c91064371';
        $json = $this->getMock($this->jsonRpcType, ['execute'], ['127.0.0.1', 8332]);
        $json
            ->expects($this->once())
            ->method('execute')
            ->willReturn($hash);

        $bitcoind = new Bitcoind($json);
        $transaction = TransactionFactory::fromHex($tx);

        $results = $bitcoind->sendrawtransaction($transaction, true);

        $this->assertEquals($hash, $results);
    }
}
