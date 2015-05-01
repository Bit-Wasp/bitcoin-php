<?php

namespace Script;


use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class OutputScriptFactoryTest extends AbstractTestCase
{

    public function testPayToAddress()
    {
        $pk1 = PrivateKeyFactory::create();

        $p2pkh = $pk1->getAddress();
        $p2pkhScript = ScriptFactory::scriptPubKey()->payToAddress($p2pkh);
        $parsedScript = $p2pkhScript->getScriptParser()->parse();
        $this->assertEquals('OP_DUP', $parsedScript[0]);
        $this->assertEquals('OP_HASH160', $parsedScript[1]);
        $this->assertEquals($pk1->getAddress()->getHash(), $parsedScript[2]->getHex());
        $this->assertEquals('OP_EQUALVERIFY', $parsedScript[3]);
        $this->assertEquals('OP_CHECKSIG', $parsedScript[4]);

        $p2sh = ScriptFactory::multisig(1, [$pk1->getPublicKey()])->getAddress();
        $p2shScript = ScriptFactory::scriptPubKey()->payToAddress($p2sh);
        $parsedScript = $p2shScript->getScriptParser()->parse();
        $this->assertEquals('OP_HASH160', $parsedScript[0]);
        $this->assertEquals($p2sh->getHash(), $parsedScript[1]->getHex());
        $this->assertEquals('OP_EQUAL', $parsedScript[2]);
    }


    public function testPayToPubKey()
    {
        $pubkey = PublicKeyFactory::fromHex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb');
        $script = ScriptFactory::scriptPubKey()->payToPubKey($pubkey);
        $parsed = $script->getScriptParser()->parse();
        $this->assertSame('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb', $parsed[0]->getHex());
        $this->assertSame('OP_CHECKSIG', $parsed[1]);
    }

    public function testPayToPubKeyHash()
    {
        $pubkey = PublicKeyFactory::fromHex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb');
        $script = ScriptFactory::scriptPubKey()->payToPubKeyHash($pubkey);
        $parsed = $script->getScriptParser()->parse();
        $this->assertSame('OP_DUP', $parsed[0]);
        $this->assertSame('OP_HASH160', $parsed[1]);
        $this->assertSame('f0cd7fab8e8f4b335931a77f114a46039068da59', $parsed[2]->getHex());
        $this->assertSame('OP_EQUALVERIFY', $parsed[3]);
    }

    public function testOutputClassify()
    {
        $ad = PrivateKeyFactory::create()->getPublicKey();
        $p2pkh = ScriptFactory::scriptPubKey()->payToPubKeyHash($ad);
        $classify = ScriptFactory::scriptPubKey()->classify($p2pkh);

        $this->assertEquals(OutputClassifier::PAYTOPUBKEYHASH, $classify->classify());
    }

    public function testPayToScriptHash()
    {
        // Script::payToScriptHash should produce a ScriptHash type script, from a different script
        $script = ScriptFactory::create()
            ->op('OP_2')
            ->push(Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb'))
            ->push(Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb'))
            ->push(Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb'))
            ->op('OP_3')
            ->op('OP_CHECKMULTISIG');

        $scriptHash = ScriptFactory::scriptPubKey()->payToScriptHash($script);
        $parsed = $scriptHash->getScriptParser()->parse();

        $this->assertSame('OP_HASH160', $parsed[0]);
        $this->assertSame('f7c29c0c6d319e33c9250fca0cb61a500621d93e', $parsed[1]->getHex());
        $this->assertSame('OP_EQUAL', $parsed[2]);
    }
}
