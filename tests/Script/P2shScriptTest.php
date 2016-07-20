<?php

namespace BitWasp\Bitcoin\Tests\Script;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class P2shScriptTest extends AbstractTestCase
{
    public function testOutputScript()
    {
        $key = PublicKeyFactory::fromHex('045b81f0017e2091e2edcd5eecf10d5bdd120a5514cb3ee65b8447ec18bfc4575c6d5bf415e54e03b1067934a0f0ba76b01c6b9ab227142ee1d543764b69d901e0');
        $script = ScriptFactory::p2sh()->multisig(1, [$key]);

        $hash = Hash::sha256ripe160($script->getBuffer());
        $outputScript = $script->getOutputScript();
        $buffer = $outputScript->getBuffer();
        $slice = $buffer->slice(2, 20);

        $address = $script->getAddress();
        $this->assertEquals($hash->getBinary(), $slice->getBinary());
        $this->assertTrue($hash->equals($address->getHash()));
    }
}
