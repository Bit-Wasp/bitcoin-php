<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script\Factory;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use Mdanter\Ecc\Primitives\Point;

class OutputScriptFactoryTest extends AbstractTestCase
{
    public function testPayToPubKey()
    {
        $x = gmp_init('61365198687444549113797742543489768233362236615628880309411002867851217134145', 10);
        $y = gmp_init('101386840280427650921972131106121862684732902285386365142828012081927687074669', 10);

        $math = Bitcoin::getMath();
        $G = Bitcoin::getGenerator();
        $point = new Point($math, $G->getCurve(), $x, $y);
        $classifier = new OutputClassifier();
        $phpecc = new EcAdapter($math, $G);

        $publicKeyComp = new PublicKey($phpecc, $point, true);
        $script = ScriptFactory::scriptPubKey()->payToPubKey($publicKeyComp);
        $parsed = $script->getScriptParser()->decode();
        $this->assertSame($publicKeyComp->getHex(), $parsed[0]->getData()->getHex());
        $this->assertSame(Opcodes::OP_CHECKSIG, $parsed[1]->getOp());
        $this->assertEquals(ScriptType::P2PK, $classifier->classify($script));

        $publicKeyUncomp = new PublicKey($phpecc, $point, false);
        $script = ScriptFactory::scriptPubKey()->payToPubKey($publicKeyUncomp);
        $parsed = $script->getScriptParser()->decode();
        $this->assertSame($publicKeyUncomp->getHex(), $parsed[0]->getData()->getHex());
        $this->assertSame(Opcodes::OP_CHECKSIG, $parsed[1]->getOp());
        $this->assertEquals(ScriptType::P2PK, $classifier->classify($script));
    }

    public function testPayToPubKeyInvalid()
    {
        $classifier = new OutputClassifier();

        $script = new Script();
        $this->assertFalse($classifier->isPayToPublicKey($script));

        $script = ScriptFactory::sequence([]);
        $this->assertFalse($classifier->isPayToPublicKey($script));

        $script = ScriptFactory::sequence([
            new Buffer('', 33),
            Opcodes::OP_DUP
        ]);
        $this->assertFalse($classifier->isPayToPublicKey($script));
    }

    public function testPayToPubKeyHash()
    {
        $pkFactory = new PublicKeyFactory();
        $pubkey = $pkFactory->fromHex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb');
        $script = ScriptFactory::scriptPubKey()->payToPubKeyHash($pubkey->getPubKeyHash());
        $parsed = $script->getScriptParser()->decode()  ;
        $this->assertSame(Opcodes::OP_DUP, $parsed[0]->getOp());
        $this->assertSame(Opcodes::OP_HASH160, $parsed[1]->getOp());
        $this->assertSame('f0cd7fab8e8f4b335931a77f114a46039068da59', $parsed[2]->getData()->getHex());
        $this->assertSame(Opcodes::OP_EQUALVERIFY, $parsed[3]->getOp());

        $classifier = new OutputClassifier();
        $this->assertEquals(ScriptType::P2PKH, $classifier->classify($script));
    }

    public function testClassifyMultisig()
    {
        $keyBufs = [
            Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb'),
            Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb'),
            Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb'),
        ];

        $script = ScriptFactory::scriptPubKey()->multisigKeyBuffers(2, $keyBufs);

        $classifier = new OutputClassifier();
        $this->assertEquals(ScriptType::MULTISIG, $classifier->classify($script));
    }

    public function testPayToScriptHash()
    {
        // Script::payToScriptHash should produce a ScriptHash type script, from a different script
        $keyBufs = [
            Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb'),
            Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb'),
            Buffer::hex('02cffc9fcdc2a4e6f5dd91aee9d8d79828c1c93e7a76949a451aab8be6a0c44feb'),
        ];

        $script = ScriptFactory::scriptPubKey()->multisigKeyBuffers(2, $keyBufs);

        $scriptHash = ScriptFactory::scriptPubKey()->payToScriptHash(Hash::sha256ripe160($script->getBuffer()));
        $parsed = $scriptHash->getScriptParser()->decode();

        $this->assertSame(Opcodes::OP_HASH160, $parsed[0]->getOp());
        $this->assertSame('f7c29c0c6d319e33c9250fca0cb61a500621d93e', $parsed[1]->getData()->getHex());
        $this->assertSame(Opcodes::OP_EQUAL, $parsed[2]->getOp());
        $this->assertEquals(ScriptType::P2SH, (new OutputClassifier())->classify($scriptHash));
    }

    public function testCoinbaseWitnessCommitment()
    {
        $witnessMerkleRoot = new Buffer(random_bytes(32), 32);
        $reservedVal = new Buffer(random_bytes(32), 32);
        $hash = Hash::sha256d(new Buffer($witnessMerkleRoot->getBinary() . $reservedVal->getBinary()));

        $expected = ScriptFactory::create()
            ->opcode(Opcodes::OP_RETURN)
            ->push(new Buffer("\xaa\x21\xa9\xed" . $hash->getBinary()))
            ->getScript();

        $this->assertEquals($expected, ScriptFactory::scriptPubKey()->witnessCoinbaseCommitment($hash));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Witness commitment hash must be exactly 32-bytes
     */
    public function testBadCoinbaseWitnessCommitment()
    {
        ScriptFactory::scriptPubKey()->witnessCoinbaseCommitment(new Buffer('', 31));
    }
}
