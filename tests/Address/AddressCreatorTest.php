<?php

namespace BitWasp\Bitcoin\Tests\Address;

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Address\SegwitAddress;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Exceptions\UnrecognizedAddressException;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\WitnessScript;
use BitWasp\Buffertools\Buffer;

class AddressCreatorTest extends \PHPUnit_Framework_TestCase
{
    public function testFromP2PKHOutputScript()
    {
        $keyHash = Hash::sha256ripe160(Buffer::hex("0328a8ed32daa433fdff209e9a413bb1ef43ecc67306d332a916533100418a7569"));
        $p2pkh = ScriptFactory::scriptPubKey()->payToPubKeyHash($keyHash);

        $addressCreator = new AddressCreator();
        $address = $addressCreator->fromOutputScript($p2pkh);

        $this->assertInstanceOf(PayToPubKeyHashAddress::class, $address);
    }

    public function testFromOutputScriptDisallowsP2shAndP2wshScripts()
    {
        $keyHash = Hash::sha256ripe160(Buffer::hex("0328a8ed32daa433fdff209e9a413bb1ef43ecc67306d332a916533100418a7569"));
        $p2pkh = new P2shScript(ScriptFactory::scriptPubKey()->payToPubKeyHash($keyHash));

        $addressCreator = new AddressCreator();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("P2shScript & WitnessScript's are not accepted by fromOutputScript");

        $addressCreator->fromOutputScript($p2pkh);
    }

    public function testFromP2SHOutputScript()
    {
        $keyHash = Hash::sha256ripe160(Buffer::hex("0328a8ed32daa433fdff209e9a413bb1ef43ecc67306d332a916533100418a7569"));
        $p2sh = ScriptFactory::scriptPubKey()->p2sh($keyHash);

        $addressCreator = new AddressCreator();
        $address = $addressCreator->fromOutputScript($p2sh);

        $this->assertInstanceOf(ScriptHashAddress::class, $address);
    }

    public function testFromP2WSHOutputScript()
    {
        $keyHash = Hash::sha256ripe160(Buffer::hex("0328a8ed32daa433fdff209e9a413bb1ef43ecc67306d332a916533100418a7569"));
        $p2pkhScript = ScriptFactory::scriptPubKey()->p2pkh($keyHash);
        $p2wsh = ScriptFactory::scriptPubKey()->p2wsh($p2pkhScript->getWitnessScriptHash());

        $addressCreator = new AddressCreator();
        $address = $addressCreator->fromOutputScript($p2wsh);

        $this->assertInstanceOf(SegwitAddress::class, $address);
    }

    public function testFromP2WPKHOutputScript()
    {
        $keyHash = Buffer::hex("", 20);
        $p2wsh = ScriptFactory::scriptPubKey()->p2wkh($keyHash);

        $addressCreator = new AddressCreator();
        $address = $addressCreator->fromOutputScript($p2wsh);

        $this->assertInstanceOf(SegwitAddress::class, $address);
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\UnrecognizedScriptForAddressException
     * @expectedExceptionMessage Script type is not associated with an address
     */
    public function testFromOutputScriptWithInvalidType()
    {
        $unknownScript = ScriptFactory::create()->op('OP_0')->op('OP_1')->getScript();
        $addressCreator = new AddressCreator();

        $this->expectException(\BitWasp\Bitcoin\Exceptions\UnrecognizedScriptForAddressException::class);
        $this->expectExceptionMessage("Script type is not associated with an address");

        $addressCreator->fromOutputScript($unknownScript);
    }

    public function testFromOutputScriptWithMultisig()
    {
        $key = Buffer::hex("0328a8ed32daa433fdff209e9a413bb1ef43ecc67306d332a916533100418a7569");
        $multisig = ScriptFactory::scriptPubKey()->multisigKeyBuffers(1, [$key]);

        $addressCreator = new AddressCreator();

        $this->expectException(\BitWasp\Bitcoin\Exceptions\UnrecognizedScriptForAddressException::class);
        $this->expectExceptionMessage("Script type is not associated with an address");

        $addressCreator->fromOutputScript($multisig);
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\UnrecognizedAddressException
     */
    public function testAddressFailswithBytes()
    {
        $add = 'LPjNgqp43ATwzMTJPM2SFoEYeyJV6pq6By';

        $network = Bitcoin::getNetwork();
        $addressCreator = new AddressCreator();
        $addressCreator->fromString($add, $network);
    }

    public function testFromP2PKHAddress()
    {
        $add = '1C1mCxRukix1KfegAY5zQQJV7samAciZpv';

        $network = Bitcoin::getNetwork();
        $addressCreator = new AddressCreator();
        $address = $addressCreator->fromString($add, $network);
        $this->assertInstanceOf(PayToPubKeyHashAddress::class, $address);
        $this->assertEquals($add, $address->getAddress($network));
    }

    public function testFromP2SHAddress()
    {
        $add = '3ESfRyvfobBCcd6N6qd84WizbBTbcgLfv6';

        $network = Bitcoin::getNetwork();
        $addressCreator = new AddressCreator();
        $address = $addressCreator->fromString($add, $network);
        $this->assertInstanceOf(ScriptHashAddress::class, $address);
        $this->assertEquals($add, $address->getAddress($network));
    }

    public function testFromP2WSHAddress()
    {
        $add = 'bc1qwqdg6squsna38e46795at95yu9atm8azzmyvckulcc7kytlcckxswvvzej';

        $network = Bitcoin::getNetwork();
        $addressCreator = new AddressCreator();
        $address = $addressCreator->fromString($add, $network);
        $this->assertInstanceOf(SegwitAddress::class, $address);
        $this->assertEquals($add, $address->getAddress($network));
    }
}
