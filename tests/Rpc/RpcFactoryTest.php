<?php

namespace BitWasp\Bitcoin\Tests\Rpc;

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Rpc\Client\ElectrumServer;
use BitWasp\Bitcoin\Rpc\RpcFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Stratum\Request\Response;
use React\Promise\FulfilledPromise;

class RpcFactoryTest extends AbstractTestCase
{
    public function testBitcoind()
    {
        $bitcoind = RpcFactory::bitcoind('127.0.0.1', 8332, 'user', 'password');
        $this->assertInstanceOf('BitWasp\Bitcoin\Rpc\Client\Bitcoind', $bitcoind);
    }

    public function testElectrum()
    {
        $math = $this->safeMath();
        $loop = \React\EventLoop\Factory::create();
        RpcFactory::electrum($math, $loop, '127.0.0.1', 99999);
    }

    public function getResponse($i, $val)
    {
        return new Response($i, $val);
    }

    public function getMockStratum()
    {
        return $this->getMockBuilder('BitWasp\Stratum\Client')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function getStratumWithResponse($value, $expects = null)
    {
        $deferred = new FulfilledPromise($this->getResponse(1, $value));

        $stratum = $this->getMockStratum();
        $stratum
            ->expects($expects ?: $this->once())
            ->method('request')
            ->willReturn($deferred);

        return $stratum;
    }

    public function getElectrumServer($value, $expects = null)
    {
        return new ElectrumServer($this->safeMath(), $this->getStratumWithResponse($value, $expects));
    }

    public function testMockTransactionGet()
    {
        $txid = '75bf3c64b181f92f0a5262e7c4ea315baa48b7dd3cf64c028a259a2c91064371';
        $txHex = '0100000001d307610c0d7ba6972bc1ba213ba766814213e63f99d443f93a87025de3649ae42f0200006b483045022100cfa227c903c88df20c2798ff48d53fed4b2a9c5e25f24a0dc63aba39a778773302206fb057829c7ecb62150a306c6c08c43cbbe58591ab2dd7430afd5d7591e25687012103ef3f4f6ba23280a262aa8ffb8743df57c97c21f38de09c76fa6fa1851a2c6540ffffffff01ef240000000000001976a9145eacfaa836d1806b13e9e42730d99c6b0914dcae88ac00000000';

        $electrum = $this->getElectrumServer($txHex);

        $call = $electrum->transactionGet($txid);
        $call->then(function (Transaction $value) use ($txid, $txHex) {
            $this->assertEquals($txid, $value->getTxId()->getHex());
            $this->assertEquals($txHex, $value->getHex());
        });
    }

    public function testMockTransactionBroadcast()
    {
        $txid = '75bf3c64b181f92f0a5262e7c4ea315baa48b7dd3cf64c028a259a2c91064371';
        $tx = TransactionFactory::fromHex('0100000001d307610c0d7ba6972bc1ba213ba766814213e63f99d443f93a87025de3649ae42f0200006b483045022100cfa227c903c88df20c2798ff48d53fed4b2a9c5e25f24a0dc63aba39a778773302206fb057829c7ecb62150a306c6c08c43cbbe58591ab2dd7430afd5d7591e25687012103ef3f4f6ba23280a262aa8ffb8743df57c97c21f38de09c76fa6fa1851a2c6540ffffffff01ef240000000000001976a9145eacfaa836d1806b13e9e42730d99c6b0914dcae88ac00000000');

        $electrum = $this->getElectrumServer($txid);

        $call = $electrum->transactionBroadcast($tx);
        $call->then(function ($value) use ($txid, $tx) {
            $this->assertEquals($txid, $value);
        });
    }

    public function testMockAddressGetBalance()
    {
        $address = '1KFHE7w8BhaENAswwryaoccDb6qcT6DbYY';

        $electrum = $this->getElectrumServer(['confirmed'=>'533506535', 'unconfirmed' => '27060000']);

        $call = $electrum->addressGetBalance(AddressFactory::fromString($address), NetworkFactory::bitcoin());
        $call->then(function (Response $value) {
            $result = $value->getResult();
            $this->assertEquals('533506535', $result['confirmed']);
            $this->assertEquals('27060000', $result['unconfirmed']);
        });
    }
    public function testMockTxGetMerkle()
    {
        $txid = '1262da2920ee12066ea72b0714c03e7becc5ac2f2fa023ce727cd597fbb6838c';
        $height = '357161';
        $merkle = ['f9a3cf50d5fc2b2b851c1a7dab04fa21cdc4131af75ef1c8e1625d2fe511e5ac','7b646c556cd845991cb6c03fad1f9098a19f2a3c428c4a37d4d8a3ffaba0a251','faf5d8c9751fa88e29aa025226cc86a78375c3b50fae55655b58cd2fb0786c90','b91ac95ccf069adf04c7e3f17d863c38ac361e14e26ee6f1320c233ca198db40','e4f3809c4d7b0d6cf18802fcf3ad0f4c12d9bfefa84ab135e0ff3c6eb28c9939','64639472c76e14afa9707db048052c77393730e66edab3db9b2b6f7cc73c7d44', 'f4956efc898718ea4facce304271f96c4d9e150a944df518d2719922a626aa52','b2e8f4a56ebc1410c1d6026b8de7f658399a56aa289323d0fdc4c9dae299748e', '3f74b9ab84da94ccf9fd13663cb8eb6e1c37d0dfe8daa024bf953fd8a7e59c18', '24cb640cd9c766413efccfc05efc4708c5370b863bb3f5a47c6445587fc7df30', 'dbf717087013bc32922f023c00af630b9d530d0998a397c00450ca6b29e2c647'];
        $electrum = $this->getElectrumServer([
            'merkle' => $merkle,
            'pos' => 679,
            'block_height' => 357161
        ]);

        $call = $electrum->transactionGetMerkle($txid, $height);
        $call->then(function (Response $value) use ($merkle) {
            $result = $value->getResult();
            $this->assertEquals($merkle, $result['merkle']);
        });
    }

    public function testMockUtxoGetAddress()
    {
        $addr = '14vgfscqdGKS5LPjdbD6fv6zPDXQTJG67N';
        $txid = '3e593a0ba05e7adc3e9363127ccb4d8151cd469e6b9ab51a1d6bec4ba2235685';
        $vout = '0';

        $electrum = $this->getElectrumServer($addr);

        $call = $electrum->utxoGetAddress($txid, $vout);
        $call->then(function (Response $value) use ($addr) {
            $result = $value->getResult();
            $this->assertEquals($addr, $result['merkle']);
        });
    }


    public function testMockListUnspent()
    {
        $addr = '14vgfscqdGKS5LPjdbD6fv6zPDXQTJG67N';
        $address = AddressFactory::fromString($addr);
        $txid = '3e593a0ba05e7adc3e9363127ccb4d8151cd469e6b9ab51a1d6bec4ba2235685';
        $utxos = [
            ['tx_hash' => $txid , 'tx_pos' => 1, 'value' => '999']
        ];

        $electrum = $this->getElectrumServer($utxos);
        $call = $electrum->addressListUnspent($address);
        $call->then(function ($value) use ($utxos) {
            $c = count($value);
            for ($i = 0; $i < $c; $i++) {
                /** @var \BitWasp\Bitcoin\Utxo\Utxo $r */
                $r = $value[$i];

                $this->assertEquals($utxos[$c]['value'], $r->getOutput()->getValue());
            }
        });
    }

    public function testMockBlockGetHeader()
    {
        $height = 359014;
        $hash = '000000000000000010579f208ff416ad97a69774b32a9e3b7abcf341042cbb73';

        $header = [
            'version' => 3,
            'prev_block_hash' => '000000000000000001c19fccb4da51cdf31dcb5afdb8b6565a3c1be8b465e79f',
            'merkle_root' => '398f1565114a2756a0b1d1a9cc37766b35e82959a66f33931ae5b431c433cfa3',
            'timestamp' => 1433205084,
            'nonce' => 2094054826,
            'bits' => 404167307
        ];

        $electrum = $this->getElectrumServer($header);
        $call = $electrum->blockGetHeader($height);
        $call->then(function (BlockHeaderInterface $value) use ($header, $hash) {
            $this->assertEquals($hash, $value->getHash()->getHex());
            $this->assertEquals('18171a8b', $value->getBits()->getHex());
            $this->assertEquals(3, $value->getVersion());
            $this->assertEquals($header['merkle_root'], $value->getMerkleRoot());
            $this->assertEquals($header['nonce'], $value->getNonce());
        });
    }
}
