<?php

namespace BitWasp\Bitcoin\Tests\Script\Classifier;

use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\InputClassifier;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class InputClassifierTest extends AbstractTestCase
{
    public function testIsKnown()
    {
        $script = new Script();
        $classifier = new InputClassifier($script);
        $this->assertEquals(InputClassifier::UNKNOWN, $classifier->classify());
    }

    public function testIsPayToScriptHash()
    {
        $privateKey = PrivateKeyFactory::create();
        $p2sh = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPublicKey());
        $scriptSig = ScriptFactory::create()->op('OP_0')->push($p2sh->getBuffer())->getScript();

        $classifier = new InputClassifier($scriptSig);
        $this->assertTrue($classifier->isPayToScriptHash());
        $this->assertEquals(InputClassifier::PAYTOSCRIPTHASH, $classifier->classify());
    }

    public function testIsPayToScriptHashFail()
    {
        $privateKey = PrivateKeyFactory::create();
        $p2sh = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPublicKey());

        $classifier = new InputClassifier($p2sh);
        $this->assertFalse($classifier->isPayToScriptHash());
    }

    public function testIsMultisigFail()
    {
        $privateKey = PrivateKeyFactory::create();
        $p2sh = ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPublicKey());

        $classifier = new InputClassifier($p2sh);
        $this->assertFalse($classifier->isMultisig());
    }
}
