<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script\Classifier;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;

class OutputClassifierTest extends AbstractTestCase
{
    public function testIsKnown()
    {
        $script = new Script();
        $classifier = new OutputClassifier();
        $this->assertEquals(ScriptType::NONSTANDARD, $classifier->classify($script));
    }

    public function getVectors()
    {
        $classifier = new OutputClassifier();
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

        $pub = new Buffer("\x04", 33);
        $this->assertTrue($classifier->isPayToPublicKey(ScriptFactory::sequence([$pub, Opcodes::OP_CHECKSIG])));
    }

    public function testIsPayToPublicKeyHash()
    {
        $classifier = new OutputClassifier();
        $this->assertFalse($classifier->isPayToPublicKeyHash(ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_DUP, Opcodes::OP_DUP, Opcodes::OP_DUP])));
        $this->assertFalse($classifier->isPayToPublicKeyHash(ScriptFactory::sequence([new Buffer(), Opcodes::OP_DUP, Opcodes::OP_DUP, Opcodes::OP_DUP, Opcodes::OP_DUP])));

        $hash = new Buffer("\x04", 20);
        $this->assertFalse($classifier->isPayToPublicKeyHash(ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_DUP, $hash, Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG])));


        $hash = new Buffer("\x04", 20);
        $this->assertTrue($classifier->isPayToPublicKeyHash(ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $hash, Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG])));
    }

    public function testIsMultisig()
    {
        $pub = Buffertools::concat(new Buffer("\x03"), new Buffer('', 32));

        $classifier = new OutputClassifier();
        $this->assertFalse($classifier->isMultisig(ScriptFactory::sequence([Opcodes::OP_0, Opcodes::OP_0, Opcodes::OP_0])));
        $this->assertFalse($classifier->isMultisig(ScriptFactory::sequence([new Buffer(), new Buffer(), Opcodes::OP_1, Opcodes::OP_CHECKMULTISIG])));
        $this->assertFalse($classifier->isMultisig(ScriptFactory::sequence([Opcodes::OP_1, Opcodes::OP_DUP, Opcodes::OP_1, Opcodes::OP_CHECKMULTISIG])));
        $this->assertFalse($classifier->isMultisig(ScriptFactory::sequence([Opcodes::OP_1, $pub, Opcodes::OP_1, Opcodes::OP_CHECKMULTISIGVERIFY])));

        $this->assertTrue($classifier->isMultisig(ScriptFactory::sequence([Opcodes::OP_1, $pub, Opcodes::OP_1, Opcodes::OP_CHECKMULTISIG])));
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
        $this->assertTrue($classifier->isWitness(ScriptFactory::sequence([Opcodes::OP_0, $hash])));
    }

    public function testIsNullData()
    {
        $classifier = new OutputClassifier();
        $embedded = Buffer::hex('41');

        $nullDataScript = ScriptFactory::sequence([Opcodes::OP_RETURN, $embedded]);

        $this->assertFalse($classifier->isNullData(new Script(new Buffer())));
        $this->assertFalse($classifier->isNullData(ScriptFactory::sequence([Buffer::hex('6a')])));
        $this->assertTrue($classifier->isNullData($nullDataScript));

        /** @var BufferInterface $extracted */
        $extracted = '';
        $classify = $classifier->classify($nullDataScript, $extracted);
        $this->assertEquals(ScriptType::NULLDATA, $classify);
        $this->assertTrue($embedded->equals($extracted));
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

        $this->assertTrue($classifier->isPayToScriptHash(ScriptFactory::sequence([Opcodes::OP_HASH160, $hash, Opcodes::OP_EQUAL])));
    }
    
    /**
     * @dataProvider getVectors
     * @param OutputClassifier $classifier
     * @param ScriptInterface $script
     * @param $eSolution
     * @param $classification
     */
    public function testCases(OutputClassifier $classifier, ScriptInterface $script, $eSolution, string $classification)
    {
        $pubKeyFactory = new PublicKeyFactory();
        $factory = ScriptFactory::scriptPubKey();
        $solution = '';
        $type = $classifier->classify($script, $solution);
        $decoded = $classifier->decode($script);

        $this->assertEquals($classification, $type);
        $this->assertEquals($type, $decoded->getType());
        $this->assertEquals($script, $decoded->getScript());
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
        $this->assertEquals($decoded->getSolution(), $solution);

        if ($type === ScriptType::P2PK) {
            $this->assertTrue($classifier->isPayToPublicKey($script));
            $this->assertEquals($script, $factory->p2pk($pubKeyFactory->fromBuffer($solution)));
        } else {
            $this->assertFalse($classifier->isPayToPublicKey($script));
        }

        if ($type === ScriptType::P2PKH) {
            $this->assertTrue($classifier->isPayToPublicKeyHash($script));
            $this->assertEquals($script, $factory->p2pkh($solution));
        } else {
            $this->assertFalse($classifier->isPayToPublicKeyHash($script));
        }

        if ($type === ScriptType::MULTISIG) {
            $this->assertTrue($classifier->isMultisig($script));
            $count = Multisig::fromScript($script)->getRequiredSigCount();
            $this->assertEquals($script, $factory->multisig($count, array_map([$pubKeyFactory, 'fromBuffer'], $solution), false));
        } else {
            $this->assertFalse($classifier->isMultisig($script));
        }

        if ($type === ScriptType::P2SH) {
            $this->assertTrue($classifier->isPayToScriptHash($script));
            $this->assertEquals(ScriptType::P2SH, $type);
            $this->assertEquals($script, $factory->p2sh($solution));
            $scriptHash = null;
            $this->assertTrue($script->isP2SH($scriptHash));
            /** @var BufferInterface $scriptHash */
            $this->assertInstanceOf(BufferInterface::class, $scriptHash);
            $this->assertTrue($scriptHash->equals($solution));
        } else {
            $this->assertFalse($classifier->isPayToScriptHash($script));
            $sh = null;
            $this->assertFalse($script->isP2SH($sh));
        }

        if ($type === ScriptType::WITNESS_COINBASE_COMMITMENT) {
            $this->assertTrue($classifier->isWitnessCoinbaseCommitment($script));
            $this->assertEquals($script, $factory->witnessCoinbaseCommitment($solution));
        } else {
            $this->assertFalse($classifier->isWitnessCoinbaseCommitment($script));
        }

        if ($type === ScriptType::P2WSH || $type === ScriptType::P2WKH) {
            $this->assertTrue($classifier->isWitness($script));
            if ($type === ScriptType::P2WSH) {
                $this->assertEquals(ScriptType::P2WSH, $type);
                $this->assertEquals($script, $factory->p2wsh($solution));
            } else {
                $this->assertEquals(ScriptType::P2WKH, $type);
                $this->assertEquals($script, $factory->p2wkh($solution));
            }
        } else {
            $this->assertFalse($classifier->isWitness($script));
        }
    }
}
