<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Factory\OutputScriptFactory;
use BitWasp\Bitcoin\Script\Factory\ScriptCreator;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Tests\Script\Factory\OutputScriptFactoryTest;

class ScriptFactoryTest extends AbstractTestCase
{
    public function testScriptPubKey()
    {
        $outputScripts = ScriptFactory::scriptPubKey();
        $this->assertInstanceOf(OutputScriptFactory::class, $outputScripts);
    }

    public function testMultisig()
    {
        $pk1 = PrivateKeyFactory::fromInt('4');
        $pk2 = PrivateKeyFactory::fromInt('50000000');

        $m = 2;
        $arbitrary = [$pk1->getPublicKey(), $pk2->getPublicKey()];

        $redeemScript = ScriptFactory::scriptPubKey()->multisig($m, $arbitrary, false);
        $info = ScriptFactory::info($redeemScript);
        foreach ($info->getKeys() as $i => $key) {
            $this->assertEquals($arbitrary[$i]->getBinary(), $key->getBinary(), 'verify false flag disables sorting');
        }

        $sorted = ScriptFactory::scriptPubKey()->multisig($m, $arbitrary, true);
        $this->assertInstanceOf(ScriptInterface::class, $sorted);
        $this->assertNotEquals($sorted->getBinary(), $redeemScript->getBinary());
    }

    public function testCreate()
    {
        $script = ScriptFactory::create(null);
        $this->assertInstanceOf(ScriptCreator::class, $script);
        $this->assertEmpty($script->getScript()->getBinary());
    }
}
