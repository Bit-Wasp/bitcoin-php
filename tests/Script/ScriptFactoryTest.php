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
        $this->assertInstanceOf($this->outScriptFactoryType, $outputScripts);
    }

    public function testScriptSig()
    {
        $inputScripts = ScriptFactory::scriptSig();
        $this->assertInstanceOf($this->inScriptFactoryType, $inputScripts);
    }

    public function testMultisig()
    {
        $pk1 = PrivateKeyFactory::fromInt('4');
        $pk2 = PrivateKeyFactory::fromInt('50000000');

        $m = 2;
        $arbitrary = [$pk1->getPublicKey(), $pk2->getPublicKey()];

        $redeemScript = ScriptFactory::scriptPubKey()->multisig($m, $arbitrary, false);
        $outputScript = ScriptFactory::scriptPubKey()->payToScriptHash($redeemScript);
        $info = ScriptFactory::info($outputScript, $redeemScript);
        $keys = $info->getKeys();
        foreach ($keys as $i => $key) {
            $this->assertEquals($arbitrary[$i]->getBinary(), $key->getBinary(), 'verify false flag disables sorting');
        }

        $sorted = ScriptFactory::scriptPubKey()->multisig($m, $arbitrary, true);
        $this->assertInstanceOf($this->scriptInterfaceType, $sorted);
        $this->assertNotEquals($sorted->getBinary(), $redeemScript->getBinary());
    }

    public function testCreate()
    {
        $script = ScriptFactory::create(null);
        $this->assertInstanceOf($this->scriptCreatorType, $script);
        $this->assertEmpty($script->getScript()->getBinary());
    }
}
