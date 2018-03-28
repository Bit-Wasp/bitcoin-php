<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Address;

use BitWasp\Bitcoin\Address\Base58AddressInterface;
use BitWasp\Bitcoin\Address\Bech32AddressInterface;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Address\SegwitAddress;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Exceptions\UnrecognizedAddressException;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Address\AddressCreator;

class AddressTest extends AbstractTestCase
{
    public function getNetwork(string $network)
    {
        switch ($network) {
            case 'btc':
                return NetworkFactory::bitcoin();
            case 'tbtc':
                return NetworkFactory::bitcoinTestnet();
            case 'zec':
                return NetworkFactory::zcash();
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
    public function testAddress(string $type, NetworkInterface $network, $data, string $address)
    {
        if ($type === 'pubkeyhash') {
            $pubKeyFactory = new PublicKeyFactory();
            $pubKey = $pubKeyFactory->fromHex($data);
            $obj = new PayToPubKeyHashAddress($pubKey->getPubKeyHash());
            $this->assertInstanceOf(PayToPubKeyHashAddress::class, $obj);

            $pubKeyHash = $pubKey->getPubKeyHash();
            $this->assertTrue($pubKeyHash->equals($obj->getHash()));

            $script = ScriptFactory::scriptPubKey()->payToPubKeyHash($obj->getHash());
        } else if ($type === 'script') {
            $redeemScript = ScriptFactory::fromHex($data);
            $obj = new ScriptHashAddress($redeemScript->getScriptHash());
            $this->assertInstanceOf(ScriptHashAddress::class, $obj);

            $scriptHash = $redeemScript->getScriptHash() ;
            $this->assertTrue($scriptHash->equals($obj->getHash()));
            $script = ScriptFactory::scriptPubKey()->payToScriptHash($obj->getHash());
        } else if ($type === 'witness') {
            $script = ScriptFactory::fromHex($data);

            $witnessProgram = null;
            $this->assertTrue($script->isWitness($witnessProgram));

            /** @var WitnessProgram $witnessProgram */
            $obj = new SegwitAddress($witnessProgram);
            $this->assertInstanceOf(SegwitAddress::class, $obj);
        } else {
            throw new \Exception('Unknown address type');
        }

        // The object should be able to serialize itself correctly
        $this->assertEquals($address, $obj->getAddress($network));

        $addrCreator = new AddressCreator();
        $fromString = $addrCreator->fromString($address, $network);
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

        $toScript = $fromString->getScriptPubKey();
        $this->assertTrue($script->equals($toScript));

        // check ourselves a bit, do we get the test fixture when
        // we pass our addresses output script?
        $addressReader = new AddressCreator();
        $addrAgain = $addressReader->fromOutputScript($fromString->getScriptPubKey());
        $this->assertEquals($addrAgain->getAddress($network), $fromString->getAddress($network));
    }

    public function testAddressFailswithBytes()
    {
        $add = 'LPjNgqp43ATwzMTJPM2SFoEYeyJV6pq6By';

        $network = Bitcoin::getNetwork();
        $addressReader = new AddressCreator();

        $this->expectException(UnrecognizedAddressException::class);

        $addressReader->fromString($add, $network);
    }

    public function testFromOutputScriptSuccess()
    {
        $outputScriptFactory = ScriptFactory::scriptPubKey();
        $pubKeyFactory = new PublicKeyFactory();
        $publicKey = $pubKeyFactory->fromHex('045b81f0017e2091e2edcd5eecf10d5bdd120a5514cb3ee65b8447ec18bfc4575c6d5bf415e54e03b1067934a0f0ba76b01c6b9ab227142ee1d543764b69d901e0');

        $pubkeyHash = $outputScriptFactory->payToPubKeyHash($publicKey->getPubKeyHash());
        $scriptHash = $outputScriptFactory->payToScriptHash(Hash::sha256ripe160($outputScriptFactory->multisig(1, [$publicKey])->getBuffer()));

        $addressCreator = new AddressCreator();

        $p2pkhAddress = $addressCreator->fromOutputScript($pubkeyHash);
        $this->assertInstanceOf(PayToPubKeyHashAddress::class, $p2pkhAddress);

        $scriptAddress = $addressCreator->fromOutputScript($scriptHash);
        $this->assertInstanceOf(ScriptHashAddress::class, $scriptAddress);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Script type is not associated with an address
     */
    public function testFromOutputScript()
    {
        $unknownScript = ScriptFactory::create()->opcode(Opcodes::OP_0, Opcodes::OP_1)->getScript();
        $addressCreator = new AddressCreator();
        $addressCreator->fromOutputScript($unknownScript);
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
