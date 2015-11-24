<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class P2shScriptTest extends AbstractTestCase
{
    public function testOutputScript()
    {
        $key = PrivateKeyFactory::create()->getPublicKey();
        $script = ScriptFactory::p2sh()->multisig(1, [$key]);

        $hash = Hash::sha256ripe160($script->getBuffer());
        $outputScript = $script->getOutputScript();
        $buffer = $outputScript->getBuffer();
        $slice = $buffer->slice(2, 20);

        $address = $script->getAddress();
        $this->assertEquals($hash->getBinary(), $slice->getBinary());
        $this->assertEquals($hash->getHex(), $address->getHash());
    }
}
