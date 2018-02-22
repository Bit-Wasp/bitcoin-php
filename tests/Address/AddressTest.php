<?php

namespace BitWasp\Bitcoin\Tests\Address;

use BitWasp\Bitcoin\Address\AddressCreator;
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
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class AddressTest extends AbstractTestCase
{

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
                Bitcoin::getDefaultNetwork(),
                $vector['script'],
                $vector['address'],
            ];
        }

        foreach ($data['pubKeyHash'] as $vector) {
            $datasets[] = [
                'pubkeyhash',
                Bitcoin::getDefaultNetwork(),
                $vector['publickey'],
                $vector['address'],
            ];
        }
        foreach ($data['witness'] as $vector) {
            switch ($vector['network']) {
                case 'btc':
                    $network = NetworkFactory::bitcoin();
                    break;
                case 'tbtc':
                    $network = NetworkFactory::bitcoinTestnet();
                    break;
                default:
                    throw new \RuntimeException("Invalid test fixture, unknown network");
            }

            $datasets[] = [
                'witness',
                $network,
                $vector['program'],
                strtolower($vector['address']),
                $vector['network'],
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
    public function testAddress($type, NetworkInterface $network, $data, $address, $t1 = null)
    {
        $addressCreator = new AddressCreator();
        if ($type === 'pubkeyhash') {
            $obj = PublicKeyFactory::fromHex($data)->getAddress();
            $script = ScriptFactory::scriptPubKey()->payToPubKeyHash($obj->getHash());
        } else if ($type === 'script') {
            $p2shScript = new P2shScript(ScriptFactory::fromHex($data));
            $obj = $p2shScript->getAddress();
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

        $this->assertEquals($address, $obj->getAddress($network));

        $fromString = $addressCreator->fromString($address, $network);
        $this->assertTrue($obj->getHash()->equals($fromString->getHash()));

        if ($fromString instanceof Base58AddressInterface) {
            $this->assertEquals($obj->getPrefixByte($network), $fromString->getPrefixByte($network));
        } else if ($fromString instanceof Bech32AddressInterface) {
            $this->assertEquals($obj->getHRP($network), $fromString->getHRP($network));
        }

        $this->assertEquals($obj->getAddress($network), $fromString->getAddress($network));

        $toScript = $fromString->getScriptPubKey();
        $this->assertTrue($script->equals($toScript));
    }

    public function testFromOutputScriptSuccess()
    {
        $outputScriptFactory = ScriptFactory::scriptPubKey();
        $publicKey = PublicKeyFactory::fromHex('045b81f0017e2091e2edcd5eecf10d5bdd120a5514cb3ee65b8447ec18bfc4575c6d5bf415e54e03b1067934a0f0ba76b01c6b9ab227142ee1d543764b69d901e0');

        $pubkeyHash = $outputScriptFactory->payToPubKeyHash($publicKey->getPubKeyHash());
        $scriptHash = $outputScriptFactory->payToScriptHash(Hash::sha256ripe160($outputScriptFactory->multisig(1, [$publicKey])->getBuffer()));

        $addressCreator = new AddressCreator();
        $p2pkhAddress = $addressCreator->fromOutputScript($pubkeyHash);
        $this->assertInstanceOf(PayToPubKeyHashAddress::class, $p2pkhAddress);

        $scriptAddress = $addressCreator->fromOutputScript($scriptHash);
        $this->assertInstanceOf(ScriptHashAddress::class, $scriptAddress);
    }
}
