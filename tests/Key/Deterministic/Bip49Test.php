<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\Deterministic;

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Bitcoin;
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

class Bip49Test extends AbstractTestCase
{
    public function testBip49WithoutPrefix()
    {
        $bip39 = new Bip39SeedGenerator();
        $adapter = Bitcoin::getEcAdapter();
        $tbtc = NetworkFactory::bitcoinTestnet();

        $ent = $bip39->getSeed("abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about");

        $hkFactory = new HierarchicalKeyFactory($adapter);
        $root = $hkFactory->fromEntropy($ent);

        $this->assertEquals(
            "tprv8ZgxMBicQKsPe5YMU9gHen4Ez3ApihUfykaqUorj9t6FDqy3nP6eoXiAo2ssvpAjoLroQxHqr3R5nE3a5dU3DHTjTgJDd7zrbniJr6nrCzd",
            $root->toExtendedPrivateKey($tbtc)
        );

        $account = $root->derivePath("49'/1'/0'");

        $this->assertEquals(
            "tprv8gRrNu65W2Msef2BdBSUgFdRTGzC8EwVXnV7UGS3faeXtuMVtGfEdidVeGbThs4ELEoayCAzZQ4uUji9DUiAs7erdVskqju7hrBcDvDsdbY",
            $account->toExtendedPrivateKey($tbtc)
        );

        $firstKey = $account->derivePath("0/0");

        $this->assertEquals(
            "cULrpoZGXiuC19Uhvykx7NugygA3k86b3hmdCeyvHYQZSxojGyXJ",
            $firstKey->getPrivateKey()->toWif($tbtc)
        );

        $this->assertEquals(
            "03a1af804ac108a8a51782198c2d034b28bf90c8803f5a53f76276fa69a4eae77f",
            $firstKey->getPrivateKey()->getPublicKey()->getHex()
        );
    }

    /**
     * @see https://github.com/satoshilabs/slips/blob/master/slip-0132.md#bitcoin-test-vectors
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter
     * @throws \Exception
     */
    public function testBip49WithHdPrefix(EcAdapterInterface $adapter)
    {
        $addrCreator = new AddressCreator();
        $bip39 = new Bip39SeedGenerator();
        $btc = NetworkFactory::bitcoin();

        $slip132Registry = new Slip132(new KeyToScriptHelper($adapter));
        $registry = new BitcoinRegistry();
        $prefix = $slip132Registry->p2shP2wpkh($registry);

        $ent = $bip39->getSeed("abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about");

        $hkFactory = new HierarchicalKeyFactory($adapter);
        $root = $hkFactory->fromEntropy($ent, $prefix->getScriptDataFactory());

        $config = new GlobalPrefixConfig([
            new NetworkConfig($btc, [
                $prefix
            ])
        ]);
        $serializer = new Base58ExtendedKeySerializer(
            new ExtendedKeySerializer($adapter, $config)
        );
        $account = $root->derivePath("49'/0'/0'");

        $this->assertEquals(
            "yprvAHwhK6RbpuS3dgCYHM5jc2ZvEKd7Bi61u9FVhYMpgMSuZS613T1xxQeKTffhrHY79hZ5PsskBjcc6C2V7DrnsMsNaGDaWev3GLRQRgV7hxF",
            $serializer->serialize($btc, $account)
        );

        $this->assertEquals(
            "ypub6Ww3ibxVfGzLrAH1PNcjyAWenMTbbAosGNB6VvmSEgytSER9azLDWCxoJwW7Ke7icmizBMXrzBx9979FfaHxHcrArf3zbeJJJUZPf663zsP",
            $serializer->serialize($btc, $account->withoutPrivateKey())
        );

        $firstAddress = $account->derivePath("0/0");

        $this->assertEquals(
            "37VucYSaXLCAsxYyAPfbSi9eh4iEcbShgf",
            $firstAddress->getAddress($addrCreator)->getAddress($btc)
        );
    }
}
