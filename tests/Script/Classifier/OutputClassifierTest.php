<?php

namespace BitWasp\Bitcoin\Tests\Script\Classifier;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;

class OutputClassifierTest extends AbstractTestCase
{
    public function testIsMultisigFail()
    {
        $script = new Script();
        $classifier = new OutputClassifier();
        $this->assertFalse($classifier->isMultisig($script));
    }

    public function testIsKnown()
    {
        $script = new Script();
        $classifier = new OutputClassifier();
        $this->assertEquals(OutputClassifier::UNKNOWN, $classifier->classify($script));
    }

    public function generateVectors()
    {
        $publicKey1 = PrivateKeyFactory::create()->getPublicKey();
        $buffer1 = $publicKey1->getBuffer();
        $p2pk = ScriptFactory::sequence([$buffer1, Opcodes::OP_CHECKSIG]);

        $hash160 = $publicKey1->getPubKeyHash();
        $p2pkh = ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $hash160, Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);

        $publicKey2 = $publicKey1->tweakAdd(1);
        $multisig = ScriptFactory::scriptPubKey()->multisig(1, [$publicKey1, $publicKey2], false);

        $p2wpkh = (new WitnessProgram(0, $hash160))->getScript();

        $hash256 = Hash::sha256($hash160);
        $p2wsh = (new WitnessProgram(0, $hash256))->getScript();

        $p2sh = ScriptFactory::sequence([Opcodes::OP_HASH160, $hash160, Opcodes::OP_EQUAL]);
        return [
            [$p2pk->getHex(), $buffer1->getHex(), OutputClassifier::PAYTOPUBKEY],
            [$p2pkh->getHex(), $hash160->getHex(), OutputClassifier::PAYTOPUBKEYHASH],
            [$multisig->getHex(), [$buffer1->getHex(), $publicKey2->getBuffer()->getHex()], OutputClassifier::MULTISIG],
            [$p2wpkh->getHex(), $hash160->getHex(), OutputClassifier::WITNESS_V0_KEYHASH],
            [$p2wsh->getHex(), $hash256->getHex(), OutputClassifier::WITNESS_V0_SCRIPTHASH],
            [$p2sh->getHex(), $hash160->getHex(), OutputClassifier::PAYTOSCRIPTHASH]
        ];
    }

    public function getVectors()
    {
        $classifier = new OutputClassifier();
        //echo json_encode($this->generateVectors(), \JSON_PRETTY_PRINT).PHP_EOL;
        $data = json_decode($this->dataFile('outputclassifier.json'), true);

        $vectors = [];
        foreach ($data as $vector) {
            $script = new Script(Buffer::hex($vector[0]));
            if (is_array($vector[1])) {
                $expectedSolution = [];
                foreach ($vector[1] as $sol) {
                    $expectedSolution[] = Buffer::hex($sol);
                }
            } else {
                $expectedSolution = Buffer::hex($vector[1]);
            }

            $vectors[] = [$classifier, $script, $expectedSolution, $vector[2]];
        }
        return $vectors;
    }

    public function testIsPayToPublicKey()
    {
        $classifier = new OutputClassifier();
        $this->assertFalse($classifier->isPayToPublicKey(ScriptFactory::sequence([Opcodes::OP_DUP])));
        $this->assertFalse($classifier->isPayToPublicKey(ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_CHECKSIG])));
        $this->assertFalse($classifier->isPayToPublicKey(ScriptFactory::sequence([new Buffer(), new Buffer()])));
        $this->assertFalse($classifier->isPayToPublicKey(ScriptFactory::sequence([new Buffer('', 20), Opcodes::OP_CHECKSIG])));
        $this->assertFalse($classifier->isPayToPublicKey(ScriptFactory::sequence([new Buffer('', 33), Opcodes::OP_CHECKMULTISIG])));

        $solution = '';
        $pub = new Buffer("\x04", 33);
        $this->assertTrue($classifier->isPayToPublicKey(ScriptFactory::sequence([$pub, Opcodes::OP_CHECKSIG]), $solution));
        $this->assertInstanceOf($this->bufferType, $solution);
        /** @var BufferInterface $solution */
        $this->assertTrue($pub->equals($solution));
    }

    public function testIsPayToPublicKeyHash()
    {
        $classifier = new OutputClassifier();
        $this->assertFalse($classifier->isPayToPublicKeyHash(ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_DUP, Opcodes::OP_DUP, Opcodes::OP_DUP])));
        $this->assertFalse($classifier->isPayToPublicKeyHash(ScriptFactory::sequence([new Buffer(), Opcodes::OP_DUP, Opcodes::OP_DUP, Opcodes::OP_DUP, Opcodes::OP_DUP])));

        $hash = new Buffer("\x04", 20);
        $this->assertFalse($classifier->isPayToPublicKeyHash(ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_DUP, $hash, Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]), $solution));


        $solution = '';
        $hash = new Buffer("\x04", 20);
        $this->assertTrue($classifier->isPayToPublicKeyHash(ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $hash, Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]), $solution));
        $this->assertInstanceOf($this->bufferType, $solution);
        /** @var BufferInterface $solution */
        $this->assertTrue($hash->equals($solution));
    }

    public function testIsMultisig()
    {
        $pub = Buffertools::concat(new Buffer("\x03"), new Buffer('', 32));

        $classifier = new OutputClassifier();
        $this->assertFalse($classifier->isMultisig(ScriptFactory::sequence([Opcodes::OP_0, Opcodes::OP_0, Opcodes::OP_0])));
        $this->assertFalse($classifier->isMultisig(ScriptFactory::sequence([new Buffer(), new Buffer(), Opcodes::OP_1, Opcodes::OP_CHECKMULTISIG])));
        $this->assertFalse($classifier->isMultisig(ScriptFactory::sequence([Opcodes::OP_1, Opcodes::OP_DUP, Opcodes::OP_1, Opcodes::OP_CHECKMULTISIG])));
        $this->assertFalse($classifier->isMultisig(ScriptFactory::sequence([Opcodes::OP_1, $pub, Opcodes::OP_1, Opcodes::OP_CHECKMULTISIGVERIFY])));

        $solution = '';
        $this->assertTrue($classifier->isMultisig(ScriptFactory::sequence([Opcodes::OP_1, $pub, Opcodes::OP_1, Opcodes::OP_CHECKMULTISIG]), $solution));
        $this->assertInternalType('array', $solution);

        $count = count($solution);
        $this->assertEquals(1, $count);
        for ($i = 0; $i < $count; $i++) {
            $this->assertInstanceOf($this->bufferType, $solution[$i]);
        }

        $this->assertTrue($pub->equals($solution[0]));

    }

    public function testIsWitness()
    {
        $classifier = new OutputClassifier();
        $this->assertFalse($classifier->isWitness(new Script(new Buffer('', 3))));
        $this->assertFalse($classifier->isWitness(ScriptFactory::sequence([new Buffer()])));
        $this->assertFalse($classifier->isWitness(ScriptFactory::sequence([Opcodes::OP_0, Opcodes::OP_0])));
        $this->assertFalse($classifier->isWitness(ScriptFactory::sequence([Opcodes::OP_0, Opcodes::OP_0, Opcodes::OP_0, Opcodes::OP_0])));

        $this->assertFalse($classifier->isWitness(ScriptFactory::sequence([Opcodes::OP_DUP, new Buffer('', 32)])));
        $this->assertFalse($classifier->isWitness(new Script(new Buffer("\x00\x0d\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01"))));

        $hash = Hash::sha256(new Buffer('string'));
        $solution = '';
        $this->assertTrue($classifier->isWitness(ScriptFactory::sequence([Opcodes::OP_0, $hash]), $solution));
        $this->assertInstanceOf($this->bufferType, $solution);
        /** @var BufferInterface $solution */
        $this->assertTrue($hash->equals($solution));
    }

    public function testIsPayToScriptHash()
    {
        $classifier = new OutputClassifier();
        $hash = new Buffer('', 20);
        $this->assertFalse($classifier->isPayToScriptHash(ScriptFactory::sequence([Opcodes::OP_DUP, $hash])));
        $this->assertFalse($classifier->isPayToScriptHash(ScriptFactory::sequence([Opcodes::OP_HASH256, $hash, Opcodes::OP_EQUAL])));
        $this->assertFalse($classifier->isPayToScriptHash(ScriptFactory::sequence([new Buffer(), $hash, Opcodes::OP_EQUAL])));

        $this->assertFalse($classifier->isPayToScriptHash(ScriptFactory::sequence([Opcodes::OP_HASH160, Opcodes::OP_0, Opcodes::OP_EQUAL])));
        $this->assertFalse($classifier->isPayToScriptHash(ScriptFactory::sequence([Opcodes::OP_HASH160, new Buffer('', 16), Opcodes::OP_EQUAL])));
        $this->assertFalse($classifier->isPayToScriptHash(ScriptFactory::sequence([Opcodes::OP_HASH160, $hash, Opcodes::OP_EQUALVERIFY])));

        $solution = '';
        $this->assertTrue($classifier->isPayToScriptHash(ScriptFactory::sequence([Opcodes::OP_HASH160, $hash, Opcodes::OP_EQUAL]), $solution));
        $this->assertInstanceOf($this->bufferType, $solution);
        /** @var BufferInterface $solution */
        $this->assertTrue($hash->equals($solution));
    }
    
    /**
     * @dataProvider getVectors
     * @param OutputClassifier $classifier
     * @param ScriptInterface $script
     * @param $eSolution
     * @param $classification
     */
    public function testCases(OutputClassifier $classifier, ScriptInterface $script, $eSolution, $classification)
    {
        $solution = '';
        $type = $classifier->classify($script, $solution);

        $this->assertEquals($classification, $type);

        if (is_array($eSolution)) {
            $this->assertInternalType('array', $solution);

            /** @var BufferInterface[] $solution */
            /** @var BufferInterface[] $eSolution */
            $size = count($eSolution);
            $this->assertEquals($size, count($solution));
            for ($i = 0; $i < $size; $i++) {
                $this->assertTrue($eSolution[$i]->equals($solution[$i]));
            }
        } else {
            /** @var BufferInterface $solution */
            /** @var BufferInterface $eSolution */
            $this->assertTrue($solution->equals($eSolution));
        }

        if ($type !== OutputClassifier::PAYTOPUBKEY) {
            $this->assertFalse($classifier->isPayToPublicKey($script));
        }

        if ($type !== OutputClassifier::PAYTOPUBKEYHASH) {
            $this->assertFalse($classifier->isPayToPublicKeyHash($script));
        }

        if ($type !== OutputClassifier::MULTISIG) {
            $this->assertFalse($classifier->isMultisig($script));
        }

        if ($type !== OutputClassifier::PAYTOSCRIPTHASH) {
            $this->assertFalse($classifier->isPayToScriptHash($script));
        }

        if ($type !== OutputClassifier::WITNESS_V0_KEYHASH && $type !== OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            $this->assertFalse($classifier->isWitness($script));
        }
    }
}
