<?php

namespace BitWasp\Bitcoin\Tests\Address;

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Address\Base58AddressInterface;
use BitWasp\Bitcoin\Address\Bech32AddressInterface;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Address\SegwitAddress;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class AddressTest extends AbstractTestCase
{

    public function getNetwork($network)
    {
        switch ($network) {
            case 'btc':
                return NetworkFactory::bitcoin();
            case 'tbtc':
                return NetworkFactory::bitcoinTestnet();
            default:
                throw new \RuntimeException("Invalid test fixture, unknown network");
        }
    }

    /**
     * @return array
     */
    public function getVectors()
    {
        $datasets = [];

        $data = json_decode($this->dataFile('addresstests.json'), true);
        foreach ($data['scriptHash'] as $vector) {
            $datasets[] = [
                'script',
                $this->getNetwork($vector['network']),
                $vector['script'],
                $vector['address'],
                $vector['hash'],
            ];
        }

        foreach ($data['pubKeyHash'] as $vector) {
            $datasets[] = [
                'pubkeyhash',
                $this->getNetwork($vector['network']),
                $vector['publickey'],
                $vector['address'],
                $vector['hash'],
            ];
        }
        foreach ($data['witness'] as $vector) {
            $datasets[] = [
                'witness',
                $this->getNetwork($vector['network']),
                $vector['program'],
                strtolower($vector['address']),
                null,
            ];
        }

        return $datasets;
    }

    /**
     * @dataProvider getVectors
     * @param $type
     * @param NetworkInterface $network
     * @param $data
     * @param $address
     * @throws \Exception
     */
    public function testAddress($type, NetworkInterface $network, $data, $address)
    {
        if ($type === 'pubkeyhash') {
            $pubKey = PublicKeyFactory::fromHex($data);
            $obj = AddressFactory::p2pkh($pubKey);
            $this->assertInstanceOf(PayToPubKeyHashAddress::class, $obj);

            $pubKeyHash = $pubKey->getPubKeyHash();
            $this->assertTrue($pubKeyHash->equals($obj->getHash()));

            $script = ScriptFactory::scriptPubKey()->payToPubKeyHash($obj->getHash());
        } else if ($type === 'script') {
            $redeemScript = ScriptFactory::fromHex($data);
            $obj = AddressFactory::p2sh($redeemScript);
            $this->assertInstanceOf(ScriptHashAddress::class, $obj);

            $scriptHash = $redeemScript->getScriptHash() ;
            $this->assertTrue($scriptHash->equals($obj->getHash()));
            $script = ScriptFactory::scriptPubKey()->payToScriptHash($obj->getHash());
        } else if ($type === 'witness') {
            $script = ScriptFactory::fromHex($data);

            $witnessProgram = null;
            $this->assertTrue($script->isWitness($witnessProgram));

            /** @var WitnessProgram $witnessProgram */
            $obj = AddressFactory::fromWitnessProgram($witnessProgram);
            $this->assertInstanceOf(SegwitAddress::class, $obj);
        } else {
            throw new \Exception('Unknown address type');
        }

        // The object should be able to serialize itself correctly
        $this->assertEquals($address, $obj->getAddress($network));

        $fromString = AddressFactory::fromString($address, $network);
        $this->assertTrue($obj->getHash()->equals($fromString->getHash()));

        if ($fromString instanceof Base58AddressInterface) {
            if ($fromString instanceof ScriptHashAddress) {
                $this->assertEquals(hex2bin($network->getP2shByte()), $obj->getPrefixByte($network));
            } else if ($fromString instanceof PayToPubKeyHashAddress) {
                $this->assertEquals(hex2bin($network->getAddressByte()), $obj->getPrefixByte($network));
            }
        } else if ($fromString instanceof Bech32AddressInterface) {
            $this->assertEquals($obj->getHRP($network), $fromString->getHRP($network));
        }

        $this->assertEquals($obj->getAddress($network), $fromString->getAddress($network));
        $this->assertTrue(AddressFactory::isValidAddress($address, $network));

        $toScript = $fromString->getScriptPubKey();
        $this->assertTrue($script->equals($toScript));

        // check ourselves a bit, do we get the test fixture when
        // we pass our addresses output script?
        $addrAgain = AddressFactory::fromOutputScript($fromString->getScriptPubKey());
        $this->assertEquals($addrAgain->getAddress($network), $fromString->getAddress($network));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddressFailswithBytes()
    {
        $add = 'LPjNgqp43ATwzMTJPM2SFoEYeyJV6pq6By';
        $this->assertFalse(AddressFactory::isValidAddress($add));

        $network = Bitcoin::getNetwork();
        AddressFactory::fromString($add, $network);
    }

    public function testFromOutputScriptSuccess()
    {
        $outputScriptFactory = ScriptFactory::scriptPubKey();
        $publicKey = PublicKeyFactory::fromHex('045b81f0017e2091e2edcd5eecf10d5bdd120a5514cb3ee65b8447ec18bfc4575c6d5bf415e54e03b1067934a0f0ba76b01c6b9ab227142ee1d543764b69d901e0');

        $pubkeyHash = $outputScriptFactory->payToPubKeyHash($publicKey->getPubKeyHash());
        $scriptHash = $outputScriptFactory->payToScriptHash(Hash::sha256ripe160($outputScriptFactory->multisig(1, [$publicKey])->getBuffer()));

        $p2pkhAddress = AddressFactory::fromOutputScript($pubkeyHash);
        $this->assertInstanceOf(PayToPubKeyHashAddress::class, $p2pkhAddress);

        $scriptAddress = AddressFactory::fromOutputScript($scriptHash);
        $this->assertInstanceOf(ScriptHashAddress::class, $scriptAddress);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Script type is not associated with an address
     */
    public function testFromOutputScript()
    {
        $unknownScript = ScriptFactory::create()->op('OP_0')->op('OP_1')->getScript();
        AddressFactory::fromOutputScript($unknownScript);
    }

    public function testAssociatedAddress()
    {
        $p2pkHex = '76a914e5d14d42026e6999da3c2cc4123f261a3253ef1688ac';
        $p2pkAddress = 'n2U7mXV4HFumkKLt7jz8LhNqKHMszTP39c';

        $p2pkhHex = '76a914b96b816f378babb1fe585b7be7a2cd16eb99b3e488ac';
        $p2pkhAddress = 'mxRN6AQJaDi5R6KmvMaEmZGe3n5ScV9u33';

        $network = NetworkFactory::bitcoinTestnet();

        $p2pkResult = AddressFactory::getAssociatedAddress(ScriptFactory::fromHex($p2pkHex))->getAddress($network);
        $this->assertEquals($p2pkAddress, $p2pkResult);

        $p2pkhResult = AddressFactory::getAssociatedAddress(ScriptFactory::fromHex($p2pkhHex))->getAddress($network);
        $this->assertEquals($p2pkhAddress, $p2pkhResult);

        $publicKey = PublicKeyFactory::fromHex('03a3f20be479bce0b17589cc526983f544dce3f80ff8b7ec46d2ee3362c3c6e775');
        $pubKeyHash = AddressFactory::p2pkh($publicKey);
        $p2pubkey = ScriptFactory::scriptPubKey()->payToPubKey($publicKey);
        $address = AddressFactory::getAssociatedAddress($p2pubkey);
        $this->assertEquals($pubKeyHash->getAddress($network), $address->getAddress($network));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Script type is not associated with an address
     */
    public function testAssociatedAddressFailure()
    {
        $s = new Script();
        AddressFactory::getAssociatedAddress($s);
    }

    public function testP2pkhIs20Bytes()
    {
        $buffer = new Buffer();
        $this->expectExceptionMessage("P2PKH address hash should be 20 bytes");
        $this->expectException(\RuntimeException::class);
        new PayToPubKeyHashAddress($buffer);
    }

    public function testP2shIs20Bytes()
    {
        $buffer = new Buffer();
        $this->expectExceptionMessage("P2SH address hash should be 20 bytes");
        $this->expectException(\RuntimeException::class);
        new ScriptHashAddress($buffer);
    }
}
