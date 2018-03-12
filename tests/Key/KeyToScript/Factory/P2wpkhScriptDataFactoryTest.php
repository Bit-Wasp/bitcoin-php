<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\KeyToScript\Factory;

use BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2shP2wshScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2shScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2wshScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2wpkhScriptDataFactory;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class P2wpkhScriptDataFactoryTest extends AbstractTestCase
{
    public function testP2wpk()
    {
        $factory = new P2wpkhScriptDataFactory();
        $this->assertEquals(ScriptType::P2WKH, $factory->getScriptType());

        $publicKeyHex = "038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b";
        $pubKeyFactory = new PublicKeyFactory();
        $publicKey = $pubKeyFactory->fromHex($publicKeyHex);
        $script = $factory->convertKey($publicKey);
        $this->assertEquals(
            "0014851a33a5ef0d4279bd5854949174e2c65b1d4500",
            $script->getScriptPubKey()->getHex()
        );

        $this->assertFalse($script->getSignData()->hasRedeemScript());
        $this->assertFalse($script->getSignData()->hasWitnessScript());
    }

    public function testP2shP2wpkh()
    {
        $factory = new P2shScriptDecorator(new P2wpkhScriptDataFactory());
        $this->assertEquals(ScriptType::P2SH . "|" . ScriptType::P2WKH, $factory->getScriptType());

        $publicKeyHex = "038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b";
        $pubKeyFactory = new PublicKeyFactory();
        $publicKey = $pubKeyFactory->fromHex($publicKeyHex);

        $script = $factory->convertKey($publicKey);

        $this->assertTrue($script->getSignData()->hasRedeemScript());
        $this->assertFalse($script->getSignData()->hasWitnessScript());

        $this->assertEquals(
            "a9140d061ae2c8ad224a81142a2e02181f5173b576b387",
            $script->getScriptPubKey()->getHex()
        );

        $this->assertEquals(
            "0014851a33a5ef0d4279bd5854949174e2c65b1d4500",
            $script->getSignData()->getRedeemScript()->getHex()
        );
    }

    public function testP2wshP2wpkhIsInvalid()
    {
        $this->expectException(DisallowedScriptDataFactoryException::class);
        new P2wshScriptDecorator(new P2wpkhScriptDataFactory());
    }

    public function testP2shP2wshP2wpkhIsInvalid()
    {
        $this->expectException(DisallowedScriptDataFactoryException::class);
        new P2shP2wshScriptDecorator(new P2wpkhScriptDataFactory());
    }
}
