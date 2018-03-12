<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\Deterministic;

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\GlobalPrefixConfig;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\NetworkConfig;
use BitWasp\Bitcoin\Key\Deterministic\Slip132\Slip132;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Network\Slip132\BitcoinRegistry;
use BitWasp\Bitcoin\Key\KeyToScript\KeyToScriptHelper;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\Base58ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class Bip44KeyAndAddressTest extends AbstractTestCase
{
    /**
     * The explicitly provided factory is P2PKH, so that
     * interacts nicely with an extended key serializer which
     * has no config. Networks p2pkh prefixes correspond to the
     * hd pub / priv bytes.
     *
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter
     * @throws \Exception
     */
    public function testBip44WithExplicitFactory(EcAdapterInterface $adapter)
    {
        // This test shows that when we specify the P2PKH
        // ScriptDataFactory, the traditional serializer
        // still works, because the prefixes are actually
        // those from the Networks hdpub / hdpriv bytes

        $addrCreator = new AddressCreator();
        $bip39 = new Bip39SeedGenerator();
        $btc = NetworkFactory::bitcoin();
        $registry = new BitcoinRegistry();
        $slip132 = new Slip132(new KeyToScriptHelper($adapter));
        $prefix = $slip132->p2pkh($registry);

        $ent = $bip39->getSeed("abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about");

        $hkFactory = new HierarchicalKeyFactory($adapter);
        $root = $hkFactory->fromEntropy($ent, $prefix->getScriptDataFactory());

        $account = $root->derivePath("44'/0'/0'");

        $this->assertEquals(
            "xprv9xpXFhFpqdQK3TmytPBqXtGSwS3DLjojFhTGht8gwAAii8py5X6pxeBnQ6ehJiyJ6nDjWGJfZ95WxByFXVkDxHXrqu53WCRGypk2ttuqncb",
            $account->toExtendedPrivateKey($btc)
        );

        $this->assertEquals(
            "xpub6BosfCnifzxcFwrSzQiqu2DBVTshkCXacvNsWGYJVVhhawA7d4R5WSWGFNbi8Aw6ZRc1brxMyWMzG3DSSSSoekkudhUd9yLb6qx39T9nMdj",
            $account->toExtendedPublicKey($btc)
        );

        $firstAddress = $account->derivePath("0/0");

        $this->assertEquals(
            "1LqBGSKuX5yYUonjxT5qGfpUsXKYYWeabA",
            $firstAddress->getAddress($addrCreator)->getAddress($btc)
        );
    }

    /**
     * The default factory is P2PKH, so serializer without a GlobalPrefixConfig
     * can serialize this fine. This necessary to adhere to old behaviour.
     *
     * @see https://github.com/satoshilabs/slips/blob/master/slip-0132.md#bitcoin-test-vectors
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     * @throws \Exception
     */
    public function testBip44WithDefaultFactory(EcAdapterInterface $adapter)
    {
        $addrCreator = new AddressCreator();
        $bip39 = new Bip39SeedGenerator();
        $btc = NetworkFactory::bitcoin();

        $ent = $bip39->getSeed("abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about");

        $hkFactory = new HierarchicalKeyFactory($adapter);
        $root = $hkFactory->fromEntropy($ent);

        $account = $root->derivePath("44'/0'/0'");

        $this->assertEquals(
            "xprv9xpXFhFpqdQK3TmytPBqXtGSwS3DLjojFhTGht8gwAAii8py5X6pxeBnQ6ehJiyJ6nDjWGJfZ95WxByFXVkDxHXrqu53WCRGypk2ttuqncb",
            $account->toExtendedPrivateKey($btc)
        );

        $this->assertEquals(
            "xpub6BosfCnifzxcFwrSzQiqu2DBVTshkCXacvNsWGYJVVhhawA7d4R5WSWGFNbi8Aw6ZRc1brxMyWMzG3DSSSSoekkudhUd9yLb6qx39T9nMdj",
            $account->toExtendedPublicKey($btc)
        );

        $firstAddress = $account->derivePath("0/0");

        $this->assertEquals(
            "1LqBGSKuX5yYUonjxT5qGfpUsXKYYWeabA",
            $firstAddress->getAddress($addrCreator)->getAddress($btc)
        );
    }

    /**
     * Using the new semantics entirely: Initialize with
     * a factory (though default), then relying on a
     * serializer with a properly initialized config to allow
     * serializing our key.
     *
     * @see https://github.com/satoshilabs/slips/blob/master/slip-0132.md#bitcoin-test-vectors
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     * @throws \Exception
     */
    public function testBip44WithConfig(EcAdapterInterface $adapter)
    {
        $addrCreator = new AddressCreator();
        $bip39 = new Bip39SeedGenerator();
        $btc = NetworkFactory::bitcoin();
        $registry = new BitcoinRegistry();

        $ent = $bip39->getSeed("abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about");

        $slip132 = new Slip132(new KeyToScriptHelper($adapter));
        $prefix = $slip132->p2pkh($registry);

        $config = new GlobalPrefixConfig([
            new NetworkConfig($btc, [
                $prefix,
            ])
        ]);
        $serializer = new Base58ExtendedKeySerializer(
            new ExtendedKeySerializer($adapter, $config)
        );

        $hkFactory = new HierarchicalKeyFactory($adapter);
        $root = $hkFactory->fromEntropy($ent, $prefix->getScriptDataFactory());

        $account = $root->derivePath("44'/0'/0'");

        $this->assertEquals(
            "xprv9xpXFhFpqdQK3TmytPBqXtGSwS3DLjojFhTGht8gwAAii8py5X6pxeBnQ6ehJiyJ6nDjWGJfZ95WxByFXVkDxHXrqu53WCRGypk2ttuqncb",
            $serializer->serialize($btc, $account)
        );

        $this->assertEquals(
            "xpub6BosfCnifzxcFwrSzQiqu2DBVTshkCXacvNsWGYJVVhhawA7d4R5WSWGFNbi8Aw6ZRc1brxMyWMzG3DSSSSoekkudhUd9yLb6qx39T9nMdj",
            $serializer->serialize($btc, $account->withoutPrivateKey())
        );

        $firstAddress = $account->derivePath("0/0");

        $this->assertEquals(
            "1LqBGSKuX5yYUonjxT5qGfpUsXKYYWeabA",
            $firstAddress->getAddress($addrCreator)->getAddress($btc)
        );
    }
}
