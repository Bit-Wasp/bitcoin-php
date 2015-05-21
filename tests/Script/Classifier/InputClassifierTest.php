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
        $scriptSig = new Script();
        $scriptSig->op('OP_0')->push($p2sh->getBuffer());

        $classifier = new InputClassifier($scriptSig);
        $this->assertTrue($classifier->isPayToScriptHash());

    }
}