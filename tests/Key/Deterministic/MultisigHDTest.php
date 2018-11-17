<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\Deterministic;

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\GlobalPrefixConfig;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\NetworkConfig;
use BitWasp\Bitcoin\Key\Deterministic\Slip132\Slip132;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Key\Deterministic\MultisigHD;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2shP2wshScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2shScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2wshScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\MultisigScriptDataFactory;
use BitWasp\Bitcoin\Key\KeyToScript\KeyToScriptHelper;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Network\Slip132\BitcoinRegistry;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\Base58ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class MultisigHDTest extends AbstractTestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Must have at least one HierarchicalKey for Multisig HD Script
     */
    public function testAlwaysProvidesKeys()
    {
        new MultisigHD(new MultisigScriptDataFactory(2, 2, true));
    }

    public function testKeyOrderIsPreserved()
    {
        $hdFactory = new HierarchicalKeyFactory();
        $p2shMultisig = new P2shScriptDecorator(new MultisigScriptDataFactory(2, 2, true));

        $key1 = $hdFactory->fromEntropy(Buffer::hex('01'));
        $key2 = $hdFactory->fromEntropy(Buffer::hex('02'));

        $hd = new MultisigHD($p2shMultisig, $key1, $key2);
        $this->assertEquals([$key1, $key2,], $hd->getKeys(), 'confirm keys has same order');

        $hd = new MultisigHD($p2shMultisig, $key2, $key1);
        $this->assertEquals([$key2, $key1,], $hd->getKeys(), 'confirm keys has same order');
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
     * @throws \BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function testGetRedeemScript(EcAdapterInterface $ecAdapter)
    {
        $hdFactory = new HierarchicalKeyFactory($ecAdapter);
        $keys = [
            $hdFactory->fromExtended('xpub661MyMwAqRbcGG5afwSiBJ37bLbnzj9VuCdKzcQgihyuRYbiA1PnhuzWzMg2H9xT7JMHWGowEfx93cxzL7KUsX9Q2hrG2ayhKf93x1uXUsV'),
            $hdFactory->fromExtended('xpub661MyMwAqRbcG2X4GYsMkLw3Rputa3aG865sQUG1mK6B4UGCyGLePHejDxiSYqWGBDUzUagLqzHq8cemTYYjHop8DRtkfqt6TAxMEznufcz'),
        ];
        $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $hd = new MultisigHD(new P2shScriptDecorator(new MultisigScriptDataFactory(2, 2, true, $pubKeySerializer)), ...$keys);
        $script = $hd->getScriptAndSignData()->getSignData()->getRedeemScript();

        // note the indexes - we know these keys will be of reversed order.
        $expected = '5221' . $keys[1]->getPublicKey()->getHex() . '21' . $keys[0]->getPublicKey()->getHex() . '52ae';

        $this->assertEquals($expected, $script->getHex());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
     * @throws \BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function testGetWitnessScript(EcAdapterInterface $ecAdapter)
    {
        $hdFactory = new HierarchicalKeyFactory($ecAdapter);
        $keys = [
            $hdFactory->fromExtended('xpub661MyMwAqRbcGG5afwSiBJ37bLbnzj9VuCdKzcQgihyuRYbiA1PnhuzWzMg2H9xT7JMHWGowEfx93cxzL7KUsX9Q2hrG2ayhKf93x1uXUsV'),
            $hdFactory->fromExtended('xpub661MyMwAqRbcG2X4GYsMkLw3Rputa3aG865sQUG1mK6B4UGCyGLePHejDxiSYqWGBDUzUagLqzHq8cemTYYjHop8DRtkfqt6TAxMEznufcz'),
        ];

        $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $hd = new MultisigHD(new P2wshScriptDecorator(new MultisigScriptDataFactory(2, 2, true, $pubKeySerializer)), ...$keys);
        $script = $hd->getScriptAndSignData()->getSignData()->getWitnessScript();

        // note the indexes - we know these keys will be of reversed order.
        $expected = '5221' . $keys[1]->getPublicKey()->getHex() . '21' . $keys[0]->getPublicKey()->getHex() . '52ae';

        $this->assertEquals($expected, $script->getHex());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
     * @throws \BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function testGetNestedWitnessScript(EcAdapterInterface $ecAdapter)
    {
        $hdFactory = new HierarchicalKeyFactory($ecAdapter);
        $keys = [
            $hdFactory->fromExtended('xpub661MyMwAqRbcGG5afwSiBJ37bLbnzj9VuCdKzcQgihyuRYbiA1PnhuzWzMg2H9xT7JMHWGowEfx93cxzL7KUsX9Q2hrG2ayhKf93x1uXUsV'),
            $hdFactory->fromExtended('xpub661MyMwAqRbcG2X4GYsMkLw3Rputa3aG865sQUG1mK6B4UGCyGLePHejDxiSYqWGBDUzUagLqzHq8cemTYYjHop8DRtkfqt6TAxMEznufcz'),
        ];

        $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $hd = new MultisigHD(new P2shP2wshScriptDecorator(new MultisigScriptDataFactory(2, 2, true, $pubKeySerializer)), ...$keys);
        $script = $hd->getScriptAndSignData()->getSignData()->getWitnessScript();

        // note the indexes - we know these keys will be of reversed order.
        $expected = '5221' . $keys[1]->getPublicKey()->getHex() . '21' . $keys[0]->getPublicKey()->getHex() . '52ae';

        $this->assertEquals($expected, $script->getHex());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException
     */
    public function testDeriveChild(EcAdapterInterface $ecAdapter)
    {
        $hdFactory = new HierarchicalKeyFactory($ecAdapter);
        $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $hd = new MultisigHD(
            new P2shScriptDecorator(new MultisigScriptDataFactory(2, 2, true, $pubKeySerializer)),
            $hdFactory->fromEntropy(Buffer::hex('01')),
            $hdFactory->fromEntropy(Buffer::hex('02'))
        );

        $child = $hd->derivePath('0');
        $childKeys = $child->getKeys();

        // The public keys were SORTED. Therefore, the 0th may not have anything to do with the initial 0th key.
        $this->assertEquals('02d5514b338973151bdedf58a08cb0c912807ac9c7e026e6dc0f11abf8073be99e', $childKeys[0]->getPublicKey()->getHex());
        $this->assertEquals('0318c49f3d850f37d93314cb9b08ed3e864af991dc109da5b3e23a0ef4c518e5d2', $childKeys[1]->getPublicKey()->getHex());
        $this->assertEquals('522102d5514b338973151bdedf58a08cb0c912807ac9c7e026e6dc0f11abf8073be99e210318c49f3d850f37d93314cb9b08ed3e864af991dc109da5b3e23a0ef4c518e5d252ae', $child->getScriptAndSignData()->getSignData()->getRedeemScript()->getHex());
        $address = $child->getAddress(new AddressCreator());
        $this->assertEquals('3GX7j2puUbkyMiWu3YYYEczJQ1ZPS9vdam', $address->getAddress());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     * @throws \BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException
     */
    public function testDerivePath(EcAdapterInterface $ecAdapter)
    {
        $hdFactory = new HierarchicalKeyFactory($ecAdapter);
        $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $hd = new MultisigHD(
            new P2shScriptDecorator(new MultisigScriptDataFactory(2, 2, true, $pubKeySerializer)),
            $hdFactory->fromEntropy(Buffer::hex('01')),
            $hdFactory->fromEntropy(Buffer::hex('02'))
        );

        $child = $hd->derivePath('0/2147483647h/1h/2147483647');
        $childKeys = $child->getKeys();

        // The public keys were SORTED. Therefore, the 0th may not have anything to do with the initial 0th key.
        $this->assertEquals('02a52960d39bede34b4c3583043d82fb2e781e83d8b7670ecee50973b95eab1199', $childKeys[0]->getPublicKey()->getHex());
        $this->assertEquals('03e53cb62d2d720b8827e214d5f306022696f0efe6efaad99dac79107e2b2f624b', $childKeys[1]->getPublicKey()->getHex());
        $address = $child->getAddress(new AddressCreator());
        $this->assertEquals('3MJdxK3kTy1THdE1mU66jR6ypUJqYkRqit', $address->getAddress());
    }

    /**
     * @throws \Exception
     * @param EcAdapterInterface $ecAdapter
     * @dataProvider getEcAdapters
     */
    public function testInitializeWithRootKeys(EcAdapterInterface $ecAdapter)
    {
        $btc = NetworkFactory::bitcoin();

        // We're using bitcoin, but not the custom BIP32 prefixes from SLIP132.
        $mnemonic = new Bip39SeedGenerator();
        $hkFactory = new HierarchicalKeyFactory($ecAdapter);

        // We're not using SLIP132, we create our own multisig factory
        $keyToScript = new KeyToScriptHelper($ecAdapter);
        $multisigFactory = $keyToScript->getP2wshFactory($keyToScript->getMultisigFactory($m = 2, $n = 2, $sortCosignKeys = true));

        // Here are two ROOT private keys - derivation to the accountNode will be required
        $key1 = $hkFactory->fromEntropy($mnemonic->getSeed("deer position make range avocado hold soldier view luggage motor sweet account"));
        $key2 = $hkFactory->fromEntropy($mnemonic->getSeed("pumpkin foster swallow stove drip detect wall bird error business public glare pioneer stick faculty moon demise crucial chat online scare hand hotel rhythm"));

        $rootNode = $hkFactory->multisig($multisigFactory, $key1, $key2);
        $accountNode = $rootNode->derivePath("48'/0'/0'/2'");
        $receivingNode = $accountNode->deriveChild(0);

        // Print out the parent public keys of the address chain
        $receiveBip32Keys = $receivingNode->getKeys();
        $this->assertEquals(
            "xprvA45iCRtuL3zXc2ys1yfrc1dJuhbRTpZ5Bg59REefd1u2jdoZVEDVPh4SzUwNzdRbhZvmAzsXmR1UKnWk75X1MHyLTtfo7JirsCaakZQ88QJ",
            $receiveBip32Keys[0]->toExtendedPrivateKey($btc)
        );

        $this->assertEquals(
            "xprvA3xZLd6bk2TLUyeNxdWrHNezf7taCRgHwibbfGU581gjC17SCRdynsxPQGKuKJSogACB4EzZ5jHfXWA8r15GHaP6JppXM9gYp5qcNAWNX65",
            $receiveBip32Keys[1]->toExtendedPrivateKey($btc)
        );

        $addrNode = $receivingNode->deriveChild(0);
        $this->assertEquals(
            "bc1q967ujw8vqe2zyxzld2y2mwxzgw8nldxhhcyka9s5w6hgrpafnezqc9lz0q",
            $addrNode->getAddress(new AddressCreator())->getAddress($btc)
        );
    }

    /**
     * @throws \Exception
     * @param EcAdapterInterface $ecAdapter
     * @dataProvider getEcAdapters
     */
    public function testTestAccountKeysUsingSlip132(EcAdapterInterface $ecAdapter)
    {
        $btc = NetworkFactory::bitcoin();

        // Here are two ACCOUNT public keys
        $key1 = "Zpub74qd5RNQomhXkYCzxSU1QUcLjpN72EV3FRJXNXWbTTiLxwtXhK6jrAccYri3iEZzhzUvBRMMfFvjfWkeXrdj3ft23y2DqcVhPqz6f1LQjXE";
        $key2 = "Zpub74hsLNTzMgUSkfez9LpE5o3esyWP1YGK4SftNir3c6xTEAoBWhmrFB86XY1VZaDLyqpbmyvfsxxT6D6crTT5oKxVViZQ5tuAfLjGe5N7HY3";

        // We'll work with some Zpubs (p2wsh multisig)
        $slip132 = new Slip132(new KeyToScriptHelper($ecAdapter));
        $ZpubPrefix = $slip132->p2wshMultisig($m = 2, $n = 2, $sortCosignKeys = true, new BitcoinRegistry());

        // NetworkConfig and GlobalPrefixConfig should be set
        // with the minimum features required for your application,
        // otherwise you'll accept keys you didn't want.
        $serializer = new Base58ExtendedKeySerializer(new ExtendedKeySerializer($ecAdapter, new GlobalPrefixConfig([
            new NetworkConfig($btc, [$ZpubPrefix,])
        ])));

        $hkFactory = new HierarchicalKeyFactory($ecAdapter, $serializer);

        $multisigHdKeys = [
            $hkFactory->fromExtended($key1, $btc),
            $hkFactory->fromExtended($key2, $btc)
        ];

        $accountNode = $hkFactory->multisig($ZpubPrefix->getScriptDataFactory(), ...$multisigHdKeys);
        $receivingNode = $accountNode->deriveChild(0);

        // Check derived receiving node xpubs
        $receiveBip32Keys = $receivingNode->getKeys();
        $this->assertEquals(
            "Zpub77dgLWW52kC9wgbwjPF6DQ6rXTm4xswWhPgrhg7bK92BLpL5JVF9GjZ8gr9ykoT3XZYy35TpWxib2KzDcfPs3JEsQJ77kJyTbTizAn1y6Am",
            $serializer->serialize($btc, $receiveBip32Keys[0])
        );

        // Check derived receiving node xpubs
        $this->assertEquals(
            "Zpub77WXUhhmSiexpdGTg365tm8YGt4DhV4jTSDJwhvzp8osoBdx1gfdfvT56eUubuhqscNQhByZ8K6h8XYWTZQdNhR7xzFgJrEw6rWG252HsJL",
            $serializer->serialize($btc, $receiveBip32Keys[1])
        );

        $addrNode = $receivingNode->deriveChild(0);
        $this->assertEquals(
            "bc1q967ujw8vqe2zyxzld2y2mwxzgw8nldxhhcyka9s5w6hgrpafnezqc9lz0q",
            $addrNode->getAddress(new AddressCreator())->getAddress($btc)
        );
    }
}
