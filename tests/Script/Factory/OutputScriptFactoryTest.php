<?php

namespace BitWasp\Bitcoin\Tests\Script\Factory;

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
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
        $parsedScript = $p2pkhScript->getScriptParser()->decode();
        $this->assertEquals(Opcodes::OP_DUP, $parsedScript[0]->getOp());
        $this->assertEquals(Opcodes::OP_HASH160, $parsedScript[1]->getOp());
        $this->assertEquals($pk1->getAddress()->getHash(), $parsedScript[2]->getData()->getHex());
        $this->assertEquals(Opcodes::OP_EQUALVERIFY, $parsedScript[3]->getOp());
        $this->assertEquals(Opcodes::OP_CHECKSIG, $parsedScript[4]->getOp());
        $this->assertEquals(OutputClassifier::PAYTOPUBKEYHASH, ScriptFactory::scriptPubKey()->classify($p2pkhScript)->classify());

        $p2sh = AddressFactory::fromScript(ScriptFactory::scriptPubKey()->multisig(1, [$pk1->getPublicKey()]));
        $p2shScript = ScriptFactory::scriptPubKey()->payToAddress($p2sh);
        $parsedScript = $p2shScript->getScriptParser()->decode();
        $this->assertEquals(Opcodes::OP_HASH160, $parsedScript[0]->getOp());
        $this->assertEquals($p2sh->getHash(), $parsedScript[1]->getData()->getHex());
        $this->assertEquals(Opcodes::OP_EQUAL, $parsedScript[2]->getOp());
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
            $parsed = $script->getScriptParser()->decode();
            $this->assertSame($pubkey->getHex(), $parsed[0]->getData()->getHex());
            $this->assertSame(Opcodes::OP_CHECKSIG, $parsed[1]->getOp());

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
        $parsed = $contract->getScriptParser()->decode();

        $this->assertEquals(Opcodes::OP_DEPTH, $parsed[0]->getOp());
        $this->assertEquals(Opcodes::OP_3, $parsed[1]->getOp());
        $this->assertEquals(Opcodes::OP_EQUAL, $parsed[2]->getOp());
        $this->assertEquals(Opcodes::OP_IF, $parsed[3]->getOp());
        $this->assertEquals(Opcodes::OP_HASH160, $parsed[4]->getOp());
        $this->assertEquals(Hash::sha256ripe160($bytes)->getBinary(), $parsed[5]->getData()->getBinary());
        $this->assertEquals(Opcodes::OP_EQUALVERIFY, $parsed[6]->getOp());

        $this->assertEquals(Opcodes::OP_2, $parsed[7]->getOp());
        $this->assertEquals($a1->getBinary(), $parsed[8]->getData()->getBinary());
        $this->assertEquals($b1->getBinary(), $parsed[9]->getData()->getBinary());
        $this->assertEquals(Opcodes::OP_2, $parsed[10]->getOp());
        $this->assertEquals(Opcodes::OP_CHECKMULTISIG, $parsed[11]->getOp());
        $this->assertEquals(Opcodes::OP_ELSE, $parsed[12]->getOp());

        $this->assertEquals(Opcodes::OP_2, $parsed[13]->getOp());
        $this->assertEquals($a2->getBinary(), $parsed[14]->getData()->getBinary());
        $this->assertEquals($b2->getBinary(), $parsed[15]->getData()->getBinary());
        $this->assertEquals(Opcodes::OP_2, $parsed[16]->getOp());
        $this->assertEquals(Opcodes::OP_CHECKMULTISIG, $parsed[17]->getOp());
        $this->assertEquals(Opcodes::OP_ENDIF, $parsed[18]->getOp());
    }

    public function testPayToPubKeyInvalid()
    {
        $script = new Script();
        $this->assertFalse(ScriptFactory::scriptPubKey()->classify($script)->isPayToPublicKey());

        $script = ScriptFactory::create()
            ->push(new Buffer())
            ->getScript();
        $this->assertFalse(ScriptFactory::scriptPubKey()->classify($script)->isPayToPublicKey());
    }

    public function testPayToPubKeyHash()
    {
        $pubkey = PublicKeyFactory::fromHex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb');
        $script = ScriptFactory::scriptPubKey()->payToPubKeyHash($pubkey);
        $parsed = $script->getScriptParser()->decode()  ;
        $this->assertSame(Opcodes::OP_DUP, $parsed[0]->getOp());
        $this->assertSame(Opcodes::OP_HASH160, $parsed[1]->getOp());
        $this->assertSame('f0cd7fab8e8f4b335931a77f114a46039068da59', $parsed[2]->getData()->getHex());
        $this->assertSame(Opcodes::OP_EQUALVERIFY, $parsed[3]->getOp());
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
            ->op('OP_CHECKMULTISIG')
            ->getScript();

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
            ->op('OP_CHECKMULTISIG')
            ->getScript();

        $scriptHash = ScriptFactory::scriptPubKey()->payToScriptHash($script);
        $parsed = $scriptHash->getScriptParser()->decode();

        $this->assertSame(Opcodes::OP_HASH160, $parsed[0]->getOp());
        $this->assertSame('f7c29c0c6d319e33c9250fca0cb61a500621d93e', $parsed[1]->getData()->getHex());
        $this->assertSame(Opcodes::OP_EQUAL, $parsed[2]->getOp());
        $this->assertEquals(OutputClassifier::PAYTOSCRIPTHASH, ScriptFactory::scriptPubKey()->classify($scriptHash)->classify());
    }
}
