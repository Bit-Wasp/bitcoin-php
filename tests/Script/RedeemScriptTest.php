<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class RedeemScriptTest extends AbstractTestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Required number of sigs exceeds number of public keys
     */
    public function testmGTn()
    {
        $pk1 = PrivateKeyFactory::create();
        new RedeemScript(4, [$pk1]);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Number of public keys is greater than 16
     */
    public function testnGT16()
    {
        $pk1 = PrivateKeyFactory::create();
        new RedeemScript(4, [
            $pk1, $pk1,$pk1, $pk1,
            $pk1, $pk1,$pk1, $pk1,
            $pk1, $pk1,$pk1, $pk1,
            $pk1, $pk1,$pk1, $pk1,
            $pk1,
        ]);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Values in $keys[] must be a PublicKey
     */
    public function testPublicKeyInterfaceRequired()
    {
        new RedeemScript(1, [PrivateKeyFactory::create()]);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No key at that index
     */
    public function testGetKeyFailure()
    {
        $pk1 = PrivateKeyFactory::create();
        $rs = new RedeemScript(1, [$pk1->getPublicKey()]);
        $rs->getKey(10);
        $this->assertEquals(1, $rs->getRequiredSigCount());
    }

    public function testGetKey()
    {
        $pk1 = PrivateKeyFactory::create()->getPublicKey();
        $rs = new RedeemScript(1, [$pk1]);
        $this->assertSame($pk1, $rs->getKey(0));
        $this->assertEquals(1, $rs->getRequiredSigCount());
    }

    public function testGetKeys()
    {
        $pk1 = PrivateKeyFactory::create()->getPublicKey();
        $arr = [$pk1];
        $rs = new RedeemScript(1, $arr);
        $this->assertEquals($arr, $rs->getKeys());
        $this->assertEquals(1, $rs->getRequiredSigCount());
    }

    public function testGetKeyCount()
    {
        $pk1 = PrivateKeyFactory::create()->getPublicKey();
        $pk2 = PrivateKeyFactory::create()->getPublicKey();
        $arr = [$pk1, $pk2];
        $rs = new RedeemScript(2, $arr);
        $this->assertEquals(2, $rs->getRequiredSigCount());
        $this->assertEquals(count($arr), $rs->getKeyCount());
        $this->assertEquals($arr, $rs->getKeys());
    }

    public function testFromScript()
    {
        $pkHex = '02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb';

        $script = ScriptFactory::create()
            ->op('OP_2')
            ->push(Buffer::hex($pkHex))
            ->push(Buffer::hex($pkHex))
            ->push(Buffer::hex($pkHex))
            ->op('OP_3')
            ->op('OP_CHECKMULTISIG');

        $rs = RedeemSCript::fromScript($script);
        $this->assertEquals(2, $rs->getRequiredSigCount());
        $this->assertEquals(3, $rs->getKeyCount());
        $this->assertEquals($pkHex, $rs->getKey(0)->getHex());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No public keys found in script
     */
    public function testInvalidScript()
    {
        $script = ScriptFactory::create()
            ->op('OP_2')
            ->op('OP_3')
            ->op('OP_CHECKMULTISIG');

        RedeemSCript::fromScript($script);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to load public key
     */
    public function testNoPublicKeysInSCript()
    {
        $script = ScriptFactory::create()
            ->op('OP_2')
            ->op('OP_3')
            ->op('OP_3')
            ->op('OP_CHECKMULTISIG');

        RedeemSCript::fromScript($script);
    }

    public function testGetOutputScript()
    {
        $pkHex = '02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb';

        $script = ScriptFactory::create()
            ->op('OP_2')
            ->push(Buffer::hex($pkHex))
            ->push(Buffer::hex($pkHex))
            ->push(Buffer::hex($pkHex))
            ->op('OP_3')
            ->op('OP_CHECKMULTISIG');

        $hash = hash('ripemd160', hash('sha256', $script->getBinary(), true), true);
        $this->assertEquals($hash, $script->getScriptHash()->getBinary());

        $rs = RedeemScript::FromSCript($script);
        $outputScript = $rs->getOutputScript();
        $parsed = $outputScript->getScriptParser()->parse();
        $this->assertEquals($hash, $parsed[1]->getBinary());
    }
}
