<?php

namespace BitWasp\Bitcoin\Tests\Script;


use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ScriptFactoryTest extends AbstractTestCase
{
    public function testScriptPubKey()
    {
        $outputScripts = ScriptFactory::scriptPubKey();
        $this->assertInstanceOf('BitWasp\Bitcoin\Script\OutputScriptFactory', $outputScripts);
    }

    public function testScriptSig()
    {
        $inputScripts = ScriptFactory::scriptSig();
        $this->assertInstanceOf('BitWasp\Bitcoin\Script\InputScriptFactory', $inputScripts);
    }

    public function testMultisig()
    {
        $pk1 = PrivateKeyFactory::create();
        $pk2 = PrivateKeyFactory::create();

        $m = 2;
        $arbitrary = [$pk1->getPublicKey(), $pk2->getPublicKey()];

        $unsorted = ScriptFactory::multisig($m, $arbitrary, false)->getKeys();
        $this->assertEquals($unsorted, $arbitrary, 'verify false flag disables sorting');

        $sorted = ScriptFactory::multisig($m, $arbitrary, true);
        $this->assertInstanceOf('BitWasp\Bitcoin\Script\RedeemScript', $sorted);
    }

    public function testCreate()
    {
        $script = ScriptFactory::create(null);
        $this->assertInstanceOf('BitWasp\Bitcoin\Script\Script', $script);
        $this->assertEmpty($script->getBinary());
    }

}