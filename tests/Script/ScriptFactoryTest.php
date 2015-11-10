<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ScriptFactoryTest extends AbstractTestCase
{
    public function testScriptPubKey()
    {
        $outputScripts = ScriptFactory::scriptPubKey();
        $this->assertInstanceOf('BitWasp\Bitcoin\Script\Factory\OutputScriptFactory', $outputScripts);
    }

    public function testScriptSig()
    {
        $inputScripts = ScriptFactory::scriptSig();
        $this->assertInstanceOf('BitWasp\Bitcoin\Script\Factory\InputScriptFactory', $inputScripts);
    }

    public function testMultisig()
    {
        $pk1 = PrivateKeyFactory::fromInt('4');
        $pk2 = PrivateKeyFactory::fromInt('50000000');

        $m = 2;
        $arbitrary = [$pk1->getPublicKey(), $pk2->getPublicKey()];

        $redeemScript = ScriptFactory::multisig($m, $arbitrary, false);
        $outputScript = ScriptFactory::scriptPubKey()->payToScriptHash($redeemScript);
        $info = ScriptFactory::info($outputScript, $redeemScript);
        $keys = $info->getKeys();
        foreach ($keys as $i => $key) {
            $this->assertEquals($arbitrary[$i]->getBinary(), $key->getBinary(), 'verify false flag disables sorting');
        }

        $sorted = ScriptFactory::multisig($m, $arbitrary, true);
        $this->assertInstanceOf('BitWasp\Bitcoin\Script\ScriptInterface', $sorted);
        $this->assertNotEquals($sorted->getBinary(), $redeemScript->getBinary());
    }

    public function testCreate()
    {
        $script = ScriptFactory::create(null);
        $this->assertInstanceOf('BitWasp\Bitcoin\Script\ScriptCreator', $script);
        $this->assertEmpty($script->getScript()->getBinary());
    }
}
