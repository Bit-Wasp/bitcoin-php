<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\KeyToScript\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2shP2wshScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2shScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2wshScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\MultisigScriptDataFactory;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptAndSignData;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class MultisigScriptDataFactoryTest extends AbstractTestCase
{
    public function testScriptTypes()
    {
        $this->assertEquals("multisig", (new MultisigScriptDataFactory(2, 2, true))->getScriptType());
        $this->assertEquals("scripthash|multisig", (new P2shScriptDecorator(new MultisigScriptDataFactory(2, 2, true)))->getScriptType());
        $this->assertEquals("witness_v0_scripthash|multisig", (new P2wshScriptDecorator(new MultisigScriptDataFactory(2, 2, true)))->getScriptType());
        $this->assertEquals("scripthash|witness_v0_scripthash|multisig", (new P2shP2wshScriptDecorator(new MultisigScriptDataFactory(2, 2, true)))->getScriptType());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \Exception
     */
    public function testTwoOfTwoWithSorting(EcAdapterInterface $ecAdapter)
    {
        $sort = true;
        $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $factory = new MultisigScriptDataFactory(2, 2, $sort, $pubKeySerializer);

        $pubKeyFactory = new PublicKeyFactory($ecAdapter);
        $publicKeyHexes = [
            "038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b",
            "02eb5a5674c3449d9504455daf0b0f809dbc983c4bb8fab7c0b04fb759f8f23a30",
        ];

        $script = $factory->convertKey(...array_map([$pubKeyFactory, 'fromHex'], $publicKeyHexes));
        $this->assertEquals(
            "5221{$publicKeyHexes[1]}21{$publicKeyHexes[0]}52ae",
            $script->getScriptPubKey()->getHex()
        );

        $this->assertFalse($script->getSignData()->hasRedeemScript());
        $this->assertFalse($script->getSignData()->hasWitnessScript());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \Exception
     */
    public function testTwoOfTwoWithoutSorting(EcAdapterInterface $ecAdapter)
    {
        $sort = false;
        $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $factory = new MultisigScriptDataFactory(2, 2, $sort, $pubKeySerializer);

        $pubKeyFactory = new PublicKeyFactory($ecAdapter);
        $publicKeyHexes = [
            "038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b",
            "02eb5a5674c3449d9504455daf0b0f809dbc983c4bb8fab7c0b04fb759f8f23a30",
        ];

        $script = $factory->convertKey(...array_map([$pubKeyFactory, 'fromHex'], $publicKeyHexes));
        $this->assertEquals(
            "5221{$publicKeyHexes[0]}21{$publicKeyHexes[1]}52ae",
            $script->getScriptPubKey()->getHex()
        );

        $this->assertFalse($script->getSignData()->hasRedeemScript());
        $this->assertFalse($script->getSignData()->hasWitnessScript());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \Exception
     */
    public function testTwoOfFiveWithoutSorting(EcAdapterInterface $ecAdapter)
    {
        $sort = false;
        $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $factory = new MultisigScriptDataFactory(2, 5, $sort, $pubKeySerializer);
        $this->assertEquals(ScriptType::MULTISIG, $factory->getScriptType());

        $pubKeyFactory = new PublicKeyFactory($ecAdapter);
        $publicKeyHexes = [
            /*0,0*/ "02e0af92e8fc45a67705704c14102fafe2a32634fdaca494c75cc6165f442b41f9",
            /*1,3*/ "032153cef42c1becb40baaa06a335d613a17c8faf75a48f986387882a71fc771ca",
            /*2,1*/ "03087f42dc60da8db78dab50ac66e11c384006eefe84acead8457e8e8d8492af6d",
            /*3,4*/ "03c96b09a587bd617c666bc72f941857d426ba3098330801bf1abdc01a56429738",
            /*4,2*/ "030d64e13e99892c2f8e5232404979cf14006b097767048272aeafc9147adb0261",
        ];

        $script = $factory->convertKey(...array_map([$pubKeyFactory, 'fromHex'], $publicKeyHexes));
        $this->assertEquals(
            "5221{$publicKeyHexes[0]}21{$publicKeyHexes[1]}21{$publicKeyHexes[2]}21{$publicKeyHexes[3]}21{$publicKeyHexes[4]}55ae",
            $script->getScriptPubKey()->getHex()
        );

        $this->assertFalse($script->getSignData()->hasRedeemScript());
        $this->assertFalse($script->getSignData()->hasWitnessScript());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \Exception
     */
    public function testChecksNumberOfKeys(EcAdapterInterface $ecAdapter)
    {
        $pubKeyFactory = new PublicKeyFactory($ecAdapter);
        $publicKeyHexes = [
            /*0,0*/ "02e0af92e8fc45a67705704c14102fafe2a32634fdaca494c75cc6165f442b41f9",
            /*1,3*/ "032153cef42c1becb40baaa06a335d613a17c8faf75a48f986387882a71fc771ca",
            /*2,1*/ "03087f42dc60da8db78dab50ac66e11c384006eefe84acead8457e8e8d8492af6d",
            /*3,4*/ "03c96b09a587bd617c666bc72f941857d426ba3098330801bf1abdc01a56429738",
            /*4,2*/ "030d64e13e99892c2f8e5232404979cf14006b097767048272aeafc9147adb0261",
        ];

        $keys = array_map([$pubKeyFactory, 'fromHex'], $publicKeyHexes);
        $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $factory = new MultisigScriptDataFactory(2, 5, false, $pubKeySerializer);

        // less than
        for ($keysToTry = 0; $keysToTry < count($keys); $keysToTry++) {
            try {
                $factory->convertKey(...array_slice($keys, 0, $keysToTry));
                $this->fail("expected exception due to too few keys");
            } catch (\Exception $e) {
                $this->assertInstanceOf(\InvalidArgumentException::class, $e);
                $this->assertEquals("Incorrect number of keys", $e->getMessage());
            }
        }

        // equal to
        $this->assertInstanceOf(ScriptAndSignData::class, $factory->convertKey(...$keys));

        // greater than
        try {
            $factory->convertKey(...array_merge($keys, [
                $pubKeyFactory->fromHex("02522516995a13d7428634ac7228cd82d0ea51efa0c8936ddb3dc45ddb3859d6b1")
            ]));
            $this->fail("expected exception due to too many keys");
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
            $this->assertEquals("Incorrect number of keys", $e->getMessage());
        }
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \Exception
     */
    public function testP2shMultisigWithSorting(EcAdapterInterface $ecAdapter)
    {
        $sort = true;
        $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $factory = new P2shScriptDecorator(new MultisigScriptDataFactory(2, 2, $sort, $pubKeySerializer));

        $pubKeyFactory = new PublicKeyFactory($ecAdapter);
        $publicKeyHexes = [
            "038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b",
            "02eb5a5674c3449d9504455daf0b0f809dbc983c4bb8fab7c0b04fb759f8f23a30",
        ];

        $scriptAndSignData = $factory->convertKey(...array_map([$pubKeyFactory, 'fromHex'], $publicKeyHexes));
        $signData = $scriptAndSignData->getSignData();
        
        $this->assertTrue($signData->hasRedeemScript());
        $this->assertFalse($signData->hasWitnessScript());

        $this->assertEquals(
            "a9141f5767cac58aa5f16ca355ae91c9545a59715a1887",
            $scriptAndSignData->getScriptPubKey()->getHex()
        );

        $this->assertEquals(
            "5221{$publicKeyHexes[1]}21{$publicKeyHexes[0]}52ae",
            $signData->getRedeemScript()->getHex()
        );
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \Exception
     */
    public function testP2wshMultisigWithSorting(EcAdapterInterface $ecAdapter)
    {
        $sort = true;
        $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $factory = new P2wshScriptDecorator(new MultisigScriptDataFactory(2, 2, $sort, $pubKeySerializer));

        $pubKeyFactory = new PublicKeyFactory($ecAdapter);
        $publicKeyHexes = [
            "038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b",
            "02eb5a5674c3449d9504455daf0b0f809dbc983c4bb8fab7c0b04fb759f8f23a30",
        ];

        $scriptAndSignData = $factory->convertKey(...array_map([$pubKeyFactory, 'fromHex'], $publicKeyHexes));
        $signData = $scriptAndSignData->getSignData();

        $this->assertFalse($signData->hasRedeemScript());
        $this->assertTrue($signData->hasWitnessScript());

        $this->assertEquals(
            "0020dcce5842a4d8e4254f94176741b498954f145b85b70c2a29f2550db3a5f7244f",
            $scriptAndSignData->getScriptPubKey()->getHex()
        );

        $this->assertEquals(
            "5221{$publicKeyHexes[1]}21{$publicKeyHexes[0]}52ae",
            $signData->getWitnessScript()->getHex()
        );
    }
    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \Exception
     */
    public function testP2shP2wshMultisigWithSorting(EcAdapterInterface $ecAdapter)
    {
        $sort = true;
        $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $factory = new P2shP2wshScriptDecorator(new MultisigScriptDataFactory(2, 2, $sort, $pubKeySerializer));

        $pubKeyFactory = new PublicKeyFactory($ecAdapter);
        $publicKeyHexes = [
            "038de63cf582d058a399a176825c045672d5ff8ea25b64d28d4375dcdb14c02b2b",
            "02eb5a5674c3449d9504455daf0b0f809dbc983c4bb8fab7c0b04fb759f8f23a30",
        ];

        $scriptAndSignData = $factory->convertKey(...array_map([$pubKeyFactory, 'fromHex'], $publicKeyHexes));
        $signData = $scriptAndSignData->getSignData();

        $this->assertTrue($signData->hasRedeemScript());
        $this->assertTrue($signData->hasWitnessScript());

        $this->assertEquals(
            "a9149fac4ec06934403320ab4b484b58207d63560b0f87",
            $scriptAndSignData->getScriptPubKey()->getHex()
        );

        $this->assertEquals(
            "0020dcce5842a4d8e4254f94176741b498954f145b85b70c2a29f2550db3a5f7244f",
            $signData->getRedeemScript()->getHex()
        );

        $this->assertEquals(
            "5221{$publicKeyHexes[1]}21{$publicKeyHexes[0]}52ae",
            $signData->getWitnessScript()->getHex()
        );
    }
}
