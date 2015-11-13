<?php

namespace BitWasp\Bitcoin\Tests\Address;

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use Symfony\Component\Yaml\Yaml;

class AddressTest extends AbstractTestCase
{

    /**
     * @return array
     */
    public function getVectors()
    {
        $datasets = [];
        $yaml = new Yaml();

        $data = $yaml->parse(file_get_contents(__DIR__ . '/../Data/addresstests.yml'));
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
            $obj = PublicKeyFactory::fromHex($data)->getAddress();
        } else if ($type === 'script') {
            $obj = AddressFactory::fromScript(new Script(Buffer::hex($data)));
        } else {
            throw new \Exception('Unknown address type');
        }

        $fromString = AddressFactory::fromString($address);
        $this->assertEquals($address, $obj->getAddress($network));
        $this->assertEquals($obj, $fromString);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddressFailswithBytes()
    {
        $add = 'LPjNgqp43ATwzMTJPM2SFoEYeyJV6pq6By';
        $network = Bitcoin::getNetwork();
        AddressFactory::fromString($add, $network);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Script type is not associated with an address
     */
    public function testFromOutputScript()
    {
        $outputScriptFactory = ScriptFactory::scriptPubKey();
        $privateKey = PrivateKeyFactory::create();
        $publicKey = $privateKey->getPublicKey();

        $pubkeyHash = $outputScriptFactory->payToPubKeyHash($publicKey);
        $scriptHash = $outputScriptFactory->payToScriptHash($outputScriptFactory->multisig(1, [$publicKey]));

        $p2pkhAddress = AddressFactory::fromOutputScript($pubkeyHash);
        $this->assertInstanceOf('BitWasp\Bitcoin\Address\PayToPubKeyHashAddress', $p2pkhAddress);

        $scriptAddress = AddressFactory::fromOutputScript($scriptHash);
        $this->assertInstanceOf('BitWasp\Bitcoin\Address\ScriptHashAddress', $scriptAddress);

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

        $p2pkResult = AddressFactory::getAssociatedAddress(ScriptFactory::fromHex($p2pkHex), $network);
        $this->assertEquals($p2pkAddress, $p2pkResult);

        $p2pkhResult = AddressFactory::getAssociatedAddress(ScriptFactory::fromHex($p2pkhHex), $network);
        $this->assertEquals($p2pkhAddress, $p2pkhResult);

        $publicKey = PublicKeyFactory::fromHex('03a3f20be479bce0b17589cc526983f544dce3f80ff8b7ec46d2ee3362c3c6e775');
        $p2pubkey = ScriptFactory::scriptPubKey()->payToPubKey($publicKey);
        $address = AddressFactory::getAssociatedAddress($p2pubkey);
        $this->assertEquals($publicKey->getAddress()->getAddress(), $address);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No address associated with this script type
     */
    public function testAssociatedAddressFailure()
    {
        $s = new Script();
        AddressFactory::getAssociatedAddress($s);
    }
}
