<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Random;
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
        $this->assertEquals(OutputClassifier::PAYTOPUBKEYHASH, ScriptFactory::scriptPubKey()->classify($p2pkhScript)->classify());

        $p2sh = ScriptFactory::multisig(1, [$pk1->getPublicKey()])->getAddress();
        $p2shScript = ScriptFactory::scriptPubKey()->payToAddress($p2sh);
        $parsedScript = $p2shScript->getScriptParser()->parse();
        $this->assertEquals('OP_HASH160', $parsedScript[0]);
        $this->assertEquals($p2sh->getHash(), $parsedScript[1]->getHex());
        $this->assertEquals('OP_EQUAL', $parsedScript[2]);
        $this->assertEquals(OutputClassifier::PAYTOSCRIPTHASH, ScriptFactory::scriptPubKey()->classify($p2shScript)->classify());
    }

    public function testPayToPubKey()
    {
        // Use loop to verify compressed & uncompressed pay to pubkey scripts
        for ($i = 0; $i < 2; $i++) {
            $compressed = $i % 2 == 0;
            $privateKey = PrivateKeyFactory::create($compressed);
            $pubkey = $privateKey->getPublicKey();

            $script = ScriptFactory::scriptPubKey()->payToPubKey($pubkey);
            $parsed = $script->getScriptParser()->parse();
            $this->assertSame($pubkey->getHex(), $parsed[0]->getHex());
            $this->assertSame('OP_CHECKSIG', $parsed[1]);

            $this->assertEquals(OutputClassifier::PAYTOPUBKEY, ScriptFactory::scriptPubKey()->classify($script)->classify());
        }
    }

    public function testPayToLightningChannel()
    {
        $random = new Random();
        $bytes = $random->bytes(20);

        $a1 = PrivateKeyFactory::fromInt(1)->getPublicKey();
        $a2 = PrivateKeyFactory::fromInt(2)->getPublicKey();
        $b1 = PrivateKeyFactory::fromInt(3)->getPublicKey();
        $b2 = PrivateKeyFactory::fromInt(4)->getPublicKey();

        $contract = ScriptFactory::scriptPubKey()->payToLightningChannel($bytes, $a1, $a2, $b1, $b2);
        $parsed = $contract->getScriptParser()->parse();

        $this->assertEquals('OP_DEPTH', $parsed[0]);
        $this->assertEquals('OP_3', $parsed[1]);
        $this->assertEquals('OP_EQUAL', $parsed[2]);
        $this->assertEquals('OP_IF', $parsed[3]);
        $this->assertEquals('OP_HASH160', $parsed[4]);
        $this->assertEquals(Hash::sha256ripe160($bytes), $parsed[5]);
        $this->assertEquals('OP_EQUALVERIFY', $parsed[6]);

        $this->assertEquals('OP_2', $parsed[7]);
        $this->assertEquals($a1->getBuffer(), $parsed[8]);
        $this->assertEquals($b1->getBuffer(), $parsed[9]);
        $this->assertEquals('OP_2', $parsed[10]);
        $this->assertEquals('OP_CHECKMULTISIG', $parsed[11]);
        $this->assertEquals('OP_ELSE', $parsed[12]);

        $this->assertEquals('OP_2', $parsed[13]);
        $this->assertEquals($a2->getBuffer(), $parsed[14]);
        $this->assertEquals($b2->getBuffer(), $parsed[15]);
        $this->assertEquals('OP_2', $parsed[16]);
        $this->assertEquals('OP_CHECKMULTISIG', $parsed[17]);
        $this->assertEquals('OP_ENDIF', $parsed[18]);
    }

    public function testPayToPubKeyInvalid()
    {
        $script = ScriptFactory::create();
        $this->assertFalse(ScriptFactory::scriptPubKey()->classify($script)->isPayToPublicKey());

        $script = ScriptFactory::create()
            ->push(new Buffer());
        $this->assertFalse(ScriptFactory::scriptPubKey()->classify($script)->isPayToPublicKey());
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
        $this->assertEquals(OutputClassifier::PAYTOPUBKEYHASH, ScriptFactory::scriptPubKey()->classify($script)->classify());
    }

    public function testClassifyMultisig()
    {
        $script = ScriptFactory::create()
            ->op('OP_2')
            ->push(Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb'))
            ->push(Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb'))
            ->push(Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb'))
            ->op('OP_3')
            ->op('OP_CHECKMULTISIG');

        $this->assertEquals(OutputClassifier::MULTISIG, ScriptFactory::scriptPubKey()->classify($script)->classify());
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
        $this->assertEquals(OutputClassifier::PAYTOSCRIPTHASH, ScriptFactory::scriptPubKey()->classify($scriptHash)->classify());
    }
}
