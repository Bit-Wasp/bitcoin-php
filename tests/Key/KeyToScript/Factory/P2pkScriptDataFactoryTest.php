<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\KeyToScript\Factory;

use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2shP2wshScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2shScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2wshScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2pkScriptDataFactory;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class P2pkScriptDataFactoryTest extends AbstractTestCase
{
    public function testP2pk()
    {
        $factory = new P2pkScriptDataFactory();
        $this->assertEquals(ScriptType::P2PK, $factory->getScriptType());

        $publicKeyHex = "038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b";
        $pubKeyFactory = new PublicKeyFactory();
        $publicKey = $pubKeyFactory->fromHex($publicKeyHex);
        $script = $factory->convertKey($publicKey);
        $this->assertEquals(
            "21038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2bac",
            $script->getScriptPubKey()->getHex()
        );
        $this->assertFalse($script->getSignData()->hasRedeemScript());
        $this->assertFalse($script->getSignData()->hasWitnessScript());
    }

    public function testP2shP2pk()
    {
        $factory = new P2shScriptDecorator(new P2pkScriptDataFactory());
        $this->assertEquals(ScriptType::P2SH . "|" . ScriptType::P2PK, $factory->getScriptType());

        $publicKeyHex = "038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b";
        $pubKeyFactory = new PublicKeyFactory();
        $publicKey = $pubKeyFactory->fromHex($publicKeyHex);

        $script = $factory->convertKey($publicKey);

        $this->assertTrue($script->getSignData()->hasRedeemScript());
        $this->assertFalse($script->getSignData()->hasWitnessScript());

        $this->assertEquals(
            "a914c99d9ebb5a4828e4e1b606dd6a51a2babebbdc0987",
            $script->getScriptPubKey()->getHex()
        );

        $this->assertEquals(
            "21038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2bac",
            $script->getSignData()->getRedeemScript()->getHex()
        );
    }

    public function testP2wshP2pk()
    {
        $factory = new P2wshScriptDecorator(new P2pkScriptDataFactory());
        $this->assertEquals(ScriptType::P2WSH . "|" . ScriptType::P2PK, $factory->getScriptType());

        $publicKeyHex = "038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b";
        $pubKeyFactory = new PublicKeyFactory();
        $publicKey = $pubKeyFactory->fromHex($publicKeyHex);

        $script = $factory->convertKey($publicKey);

        $this->assertFalse($script->getSignData()->hasRedeemScript());
        $this->assertTrue($script->getSignData()->hasWitnessScript());

        $this->assertEquals(
            "00200f9ea7bae7166c980169059e39443ed13324495b0d6678ce716262e879591210",
            $script->getScriptPubKey()->getHex()
        );

        $this->assertEquals(
            "21038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2bac",
            $script->getSignData()->getWitnessScript()->getHex()
        );
    }

    public function testP2shP2wshP2pk()
    {
        $factory = new P2shP2wshScriptDecorator(new P2pkScriptDataFactory());
        $this->assertEquals(ScriptType::P2SH . "|" . ScriptType::P2WSH . "|" . ScriptType::P2PK, $factory->getScriptType());

        $publicKeyHex = "038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b";
        $pubKeyFactory = new PublicKeyFactory();
        $publicKey = $pubKeyFactory->fromHex($publicKeyHex);

        $script = $factory->convertKey($publicKey);

        $this->assertTrue($script->getSignData()->hasRedeemScript());
        $this->assertTrue($script->getSignData()->hasWitnessScript());

        $this->assertEquals(
            "a9146d185c7042d01ea8276dc6be6603101dc441d8a487",
            $script->getScriptPubKey()->getHex()
        );

        $this->assertEquals(
            "00200f9ea7bae7166c980169059e39443ed13324495b0d6678ce716262e879591210",
            $script->getSignData()->getRedeemScript()->getHex()
        );

        $this->assertEquals(
            "21038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2bac",
            $script->getSignData()->getWitnessScript()->getHex()
        );
    }
}
