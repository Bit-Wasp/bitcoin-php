<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\KeyToScript\Factory;

use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2shP2wshScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2shScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2wshScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2pkhScriptDataFactory;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class P2pkhScriptDataFactoryTest extends AbstractTestCase
{
    public function testP2pk()
    {
        $factory = new P2pkhScriptDataFactory();
        $this->assertEquals(ScriptType::P2PKH, $factory->getScriptType());

        $privKeyHex = "8de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b";
        $privKeyFactory = new PrivateKeyFactory(false);
        $privKey = $privKeyFactory->fromHex($privKeyHex);
        $publicKey = $privKey->getPublicKey();
        $script = $factory->convertKey($publicKey);
        $this->assertEquals(
            "76a914f61c6c67fb0e03bab869ce2243e87e60655b09cd88ac",
            $script->getScriptPubKey()->getHex()
        );

        $this->assertFalse($script->getSignData()->hasRedeemScript());
        $this->assertFalse($script->getSignData()->hasWitnessScript());

        $script = $factory->convertKey($privKey);
        $this->assertEquals(
            "76a914f61c6c67fb0e03bab869ce2243e87e60655b09cd88ac",
            $script->getScriptPubKey()->getHex()
        );

        $this->assertFalse($script->getSignData()->hasRedeemScript());
        $this->assertFalse($script->getSignData()->hasWitnessScript());
    }

    public function testP2shP2pkh()
    {
        $factory = new P2shScriptDecorator(new P2pkhScriptDataFactory());
        $this->assertEquals(ScriptType::P2SH . "|" . ScriptType::P2PKH, $factory->getScriptType());

        $publicKeyHex = "038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b";
        $pubKeyFactory = new PublicKeyFactory();
        $publicKey = $pubKeyFactory->fromHex($publicKeyHex);

        $script = $factory->convertKey($publicKey);

        $this->assertTrue($script->getSignData()->hasRedeemScript());
        $this->assertFalse($script->getSignData()->hasWitnessScript());

        $this->assertEquals(
            "a9142162ff7c23d47a0c331f95c67d7c3e22abb12a0287",
            $script->getScriptPubKey()->getHex()
        );

        $this->assertEquals(
            "76a914851a33a5ef0d4279bd5854949174e2c65b1d450088ac",
            $script->getSignData()->getRedeemScript()->getHex()
        );
    }

    public function testP2wshP2pk()
    {
        $factory = new P2wshScriptDecorator(new P2pkhScriptDataFactory());
        $this->assertEquals(ScriptType::P2WSH . "|" . ScriptType::P2PKH, $factory->getScriptType());

        $publicKeyHex = "038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b";
        $pubKeyFactory = new PublicKeyFactory();
        $publicKey = $pubKeyFactory->fromHex($publicKeyHex);

        $script = $factory->convertKey($publicKey);

        $this->assertFalse($script->getSignData()->hasRedeemScript());
        $this->assertTrue($script->getSignData()->hasWitnessScript());

        $this->assertEquals(
            "0020578db4b54a6961060b71385c17d3280379a557224c52b11b19a3a1c1eef606a0",
            $script->getScriptPubKey()->getHex()
        );

        $this->assertEquals(
            "76a914851a33a5ef0d4279bd5854949174e2c65b1d450088ac",
            $script->getSignData()->getWitnessScript()->getHex()
        );
    }

    public function testP2shP2wshP2pk()
    {
        $factory = new P2shP2wshScriptDecorator(new P2pkhScriptDataFactory());
        $this->assertEquals(ScriptType::P2SH . "|" . ScriptType::P2WSH . "|" . ScriptType::P2PKH, $factory->getScriptType());

        $publicKeyHex = "038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b";
        $pubKeyFactory = new PublicKeyFactory();
        $publicKey = $pubKeyFactory->fromHex($publicKeyHex);

        $script = $factory->convertKey($publicKey);

        $this->assertTrue($script->getSignData()->hasRedeemScript());
        $this->assertTrue($script->getSignData()->hasWitnessScript());

        $this->assertEquals(
            "a91444a641c4e06eb6118c99e5ed29954b705b50fb6a87",
            $script->getScriptPubKey()->getHex()
        );

        $this->assertEquals(
            "0020578db4b54a6961060b71385c17d3280379a557224c52b11b19a3a1c1eef606a0",
            $script->getSignData()->getRedeemScript()->getHex()
        );

        $this->assertEquals(
            "76a914851a33a5ef0d4279bd5854949174e2c65b1d450088ac",
            $script->getSignData()->getWitnessScript()->getHex()
        );
    }
}
