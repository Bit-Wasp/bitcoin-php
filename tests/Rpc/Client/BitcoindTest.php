<?php

namespace BitWasp\Bitcoin\Tests\Rpc\Client;

use BitWasp\Bitcoin\JsonRpc\JsonRpcClient;
use BitWasp\Bitcoin\Rpc\Client\Bitcoind;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

class BitcoindTest extends AbstractTestCase
{
    private $jsonRpcType = 'BitWasp\Bitcoin\JsonRpc\JsonRpcClient';

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
        //
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
