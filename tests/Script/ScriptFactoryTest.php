<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Factory\OutputScriptFactory;
use BitWasp\Bitcoin\Script\Factory\ScriptCreator;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ScriptFactoryTest extends AbstractTestCase
{
    public function testScriptPubKey()
    {
        $outputScripts = ScriptFactory::scriptPubKey();
        $this->assertInstanceOf(OutputScriptFactory::class, $outputScripts);
    }

    public function testMultisig()
    {
        $factory = new PrivateKeyFactory(false);
        $pk1 = $factory->fromHex('9999999999999999999999999999999999999999999999999999999999999999');
        $pk2 = $factory->fromHex('abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234');

        $m = 2;
        $arbitrary = [$pk1->getPublicKey(), $pk2->getPublicKey()];

        $redeemScript = ScriptFactory::scriptPubKey()->multisig($m, $arbitrary, false);
        $info = Multisig::fromScript($redeemScript);
        foreach ($info->getKeyBuffers() as $i => $key) {
            $this->assertTrue($arbitrary[$i]->getBuffer()->equals($key), 'verify false flag disables sorting');
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
