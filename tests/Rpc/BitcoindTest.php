<?php

namespace BitWasp\Bitcoin\Tests\Rpc\Client;

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Amount;
use BitWasp\Bitcoin\JsonRpc\JsonRpcClient;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Rpc\Client\Bitcoind;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;

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

    public function testGetRpcClient()
    {
        $json = new JsonRpcClient("127.0.0.1", 30929);
        $bitcoind = new Bitcoind($json);
        $this->assertEquals($json, $bitcoind->getRpcClient());
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
     * Get a block count should return an integer
     */
    public function testMockGetBlockCount()
    {
        $count = '409524';
        $json = $this->getMock($this->jsonRpcType, ['execute'], ['127.0.0.1', 8332]);
        $json
            ->expects($this->once())
            ->method('execute')
            ->willReturn($count);

        $bitcoind = new Bitcoind($json);
        $results = $bitcoind->getblockcount();
        $this->assertEquals($count, $results);
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
        $this->assertEquals($hash, $results->getTxId()->getHex());
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
        $this->assertEquals($txHex, $block->getTransactions()->get(0)->getHex());
    }

    public function testCreateRawTransaction()
    {
        $inputs = [[
            'txid' => '4141414141414141414141414141414141414141414141414141414141414141',
            'vout' => 0
        ]
        ];

        $outputs = [[
            '15HwMfmBPLgwrwp4cyDMpnR5V4SNUB3Pip' => '1'
        ]];

        $i = [
            new TransactionInput('4141414141414141414141414141414141414141414141414141414141414141', 0)
        ];
        $o = [
            new TransactionOutput(Amount::COIN, new Script(Buffer::hex('76a9142f14886d6dde16d37e8149f603b18c879f486c5388ac')))
        ];

        $t = TransactionFactory::build()
            ->version(1)
            ->inputs($i)
            ->outputs($o)
            ->get();

        $json = $this->getMock($this->jsonRpcType, ['execute'], ['127.0.0.1', 8332]);
        $json->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn($t);

        $bitcoind = new Bitcoind($json);
        $tx = $bitcoind->createrawtransaction($inputs, $outputs);

        $this->assertEquals('010000000141414141414141414141414141414141414141414141414141414141414141410000000000ffffffff0100e1f505000000001976a9142f14886d6dde16d37e8149f603b18c879f486c5388ac00000000', $tx->getHex());
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

    public function testListUnspent()
    {
        $json = $this->getMock($this->jsonRpcType, ['execute'], ['127.0.0.1', 8332]);
        $json->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn(json_decode('[{
        "txid" : "f0802077ad259c7e49257b55851943292235e330e3287a88589f9b2d2c8adb24",
        "vout" : 0,
        "address" : "1Cemh3cKQm6q9qCzgBAgguv17w4dsLNvSH",
        "account" : "",
        "scriptPubKey" : "76a9147fce14745de0888d51abd04ed10a6cca57157c6588ac",
        "amount" : 0.01000000,
        "confirmations" : 21,
        "spendable" : true
    }]', true));

        $bitcoind = new Bitcoind($json);
        $unspent = $bitcoind->listunspent(0, 100, [AddressFactory::fromString('1Cemh3cKQm6q9qCzgBAgguv17w4dsLNvSH')]);

        $expected = new Utxo(
            'f0802077ad259c7e49257b55851943292235e330e3287a88589f9b2d2c8adb24',
            0,
            new TransactionOutput(
                1000000,
                new Script(Buffer::hex('76a9147fce14745de0888d51abd04ed10a6cca57157c6588ac'))
            )
        );

        $this->assertEquals(1, count($unspent));
        $this->assertEquals($expected, $unspent[0]);
    }

    public function testSignRawTransaction()
    {
        $wif = 'Ky8RfHnFPRDnGARbLE111N5fRgHhQuDkVGqr5iBSonKuN5V2e2HS';
        $priv = PrivateKeyFactory::fromWif($wif);

        $txid = '4f00a317a1c3ec76a87dc1f223d9317ff7520ca3838331ab0c57ff56937ffd7d';
        $vout = 1;
        $t = TransactionFactory::build()
            ->input($txid, $vout)
            ->payToAddress(AddressFactory::fromString('1BvGQa7QHK3M4t2DXKePrKEpLRisM8eVys'), '9794466')
            ->get();

        $o = $t->getOutput(0);

        $json = $this->getMock($this->jsonRpcType, ['execute'], ['127.0.0.1', 8332]);
        $json->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn(json_decode('{
    "hex" : "01000000017dfd7f9356ff570cab318383a30c52f77f31d923f2c17da876ecc3a117a3004f010000006a473044022067fae0180dc75d4e4713502d80d915e912f73f84d41a7e56c0cedc0ef6b12bd7022048fab8f21508e6e7a4221047cf2afa5e63abfc96a1e8a028c37b9734b38140970121027431c86d5f701959a92c5c47bce58e2db85b377a263ede522cd7aaa77f1384a4ffffffff0182de0e00000000001976a91477c42a179e7f74bc5851ea7b0e326f430418625b88ac00000000",
    "complete" : true
}', true));

        $inputs = [[
            'txid' => $txid,
            'vout' => $vout,
            'scriptPubKey' => $o->getScript()->getHex()
        ]];

        $bitcoind = new Bitcoind($json);
        $tx = $bitcoind->signrawtransaction($t, $inputs, [$priv]);
        $this->assertEquals(TransactionFactory::fromHex('01000000017dfd7f9356ff570cab318383a30c52f77f31d923f2c17da876ecc3a117a3004f010000006a473044022067fae0180dc75d4e4713502d80d915e912f73f84d41a7e56c0cedc0ef6b12bd7022048fab8f21508e6e7a4221047cf2afa5e63abfc96a1e8a028c37b9734b38140970121027431c86d5f701959a92c5c47bce58e2db85b377a263ede522cd7aaa77f1384a4ffffffff0182de0e00000000001976a91477c42a179e7f74bc5851ea7b0e326f430418625b88ac00000000'), $tx);

    }
}
