<?php

namespace BitWasp\Bitcoin\Tests\Serializer\Key\HierarchicalKey;

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\GlobalPrefixConfig;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\NetworkConfig;
use BitWasp\Bitcoin\Key\Deterministic\Slip132\Slip132;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Network\Slip132\BitcoinRegistry;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2pkhScriptDataFactory;
use BitWasp\Bitcoin\Key\KeyToScript\KeyToScriptHelper;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\Base58ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class ExtendedKeySerializerTest extends AbstractTestCase
{
    protected $seed = '000102030405060708090a0b0c0d0e0f';

    /**
     * @param Base58ExtendedKeySerializer $serializer
     * @param NetworkInterface $network
     * @param $serialized
     */
    public function checkConsistency(
        Base58ExtendedKeySerializer $serializer,
        NetworkInterface $network,
        $serialized
    ) {
        $parsed = $serializer->parse($network, $serialized);
        $serializedAgain = $serializer->serialize($network, $parsed);
        $this->assertEquals(
            $serialized,
            $serializedAgain,
            "should equal, we tested encode(decode()) == strval"
        );
    }

    /**
     * @param Base58ExtendedKeySerializer $serializer
     * @param HierarchicalKey $key
     * @param AddressCreator $addressCreator
     * @param NetworkInterface $network
     * @param $expectedPriv
     * @param $expectedPub
     * @param $expectedAddress
     */
    public function checkFixture(
        Base58ExtendedKeySerializer $serializer,
        HierarchicalKey $key,
        AddressCreator $addressCreator,
        NetworkInterface $network,
        $expectedPriv,
        $expectedPub,
        $expectedAddress
    ) {

        $serializedPriv = $serializer->serialize($network, $key);
        $this->assertEquals(
            $expectedPriv,
            $serializedPriv
        );
        $this->checkConsistency($serializer, $network, $serializedPriv);

        $serializedPub = $serializer->serialize($network, $key->withoutPrivateKey());
        $this->assertEquals(
            $expectedPub,
            $serializedPub
        );
        $this->checkConsistency($serializer, $network, $serializedPub);

        $this->assertEquals(
            $expectedAddress,
            $key->getAddress($addressCreator)->getAddress($network)
        );
    }

    public function getEquivalentConfigurations()
    {
        $network = NetworkFactory::bitcoin();

        $serializer = [];
        foreach ($this->getEcAdapters() as $adapterRow) {
            $adapter = $adapterRow[0];
            $bitcoinPrefixes = new BitcoinRegistry();
            $slip132 = new Slip132(new KeyToScriptHelper($adapter));
            $prefix = $slip132->p2pkh($bitcoinPrefixes);
            $globalConfig = new GlobalPrefixConfig([
                new NetworkConfig(
                    $network,
                    [$prefix]
                )
            ]);
            $serializer[] = [$adapter, $network, new Base58ExtendedKeySerializer(new ExtendedKeySerializer($adapter))];
            $serializer[] = [$adapter, $network, new Base58ExtendedKeySerializer(new ExtendedKeySerializer($adapter, $globalConfig))];
        }
        return $serializer;
    }

    /**
     * @dataProvider getEquivalentConfigurations
     * @param EcAdapterInterface $adapter
     * @param NetworkInterface $network
     * @param Base58ExtendedKeySerializer $serializer
     * @throws \Exception
     */
    public function testBasicElectrumBip32(EcAdapterInterface $adapter, NetworkInterface $network, Base58ExtendedKeySerializer $serializer)
    {
        $addressCreator = new AddressCreator();
        $scriptFactory = new P2pkhScriptDataFactory(EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $adapter));

        $entropy = Buffer::hex($this->seed);
        $key = HierarchicalKeyFactory::fromEntropy($entropy, $adapter, $scriptFactory);

        $this->checkFixture(
            $serializer,
            $key,
            $addressCreator,
            $network,
            "xprv9s21ZrQH143K3QTDL4LXw2F7HEK3wJUD2nW2nRk4stbPy6cq3jPPqjiChkVvvNKmPGJxWUtg6LnF5kejMRNNU3TGtRBeJgk33yuGBxrMPHi",
            "xpub661MyMwAqRbcFtXgS5sYJABqqG9YLmC4Q1Rdap9gSE8NqtwybGhePY2gZ29ESFjqJoCu1Rupje8YtGqsefD265TMg7usUDFdp6W1EGMcet8",
            "15mKKb2eos1hWa6tisdPwwDC1a5J1y9nma"
        );

        $key1 = $key->derivePath("0'");

        $this->checkFixture(
            $serializer,
            $key1,
            $addressCreator,
            $network,
            "xprv9uHRZZhk6KAJC1avXpDAp4MDc3sQKNxDiPvvkX8Br5ngLNv1TxvUxt4cV1rGL5hj6KCesnDYUhd7oWgT11eZG7XnxHrnYeSvkzY7d2bhkJ7",
            "xpub68Gmy5EdvgibQVfPdqkBBCHxA5htiqg55crXYuXoQRKfDBFA1WEjWgP6LHhwBZeNK1VTsfTFUHCdrfp1bgwQ9xv5ski8PX9rL2dZXvgGDnw",
            "19Q2WoS5hSS6T8GjhK8KZLMgmWaq4neXrh"
        );

        $key2 = $key1->derivePath("1");

        $this->checkFixture(
            $serializer,
            $key2,
            $addressCreator,
            $network,
            "xprv9wTYmMFdV23N2TdNG573QoEsfRrWKQgWeibmLntzniatZvR9BmLnvSxqu53Kw1UmYPxLgboyZQaXwTCg8MSY3H2EU4pWcQDnRnrVA1xe8fs",
            "xpub6ASuArnXKPbfEwhqN6e3mwBcDTgzisQN1wXN9BJcM47sSikHjJf3UFHKkNAWbWMiGj7Wf5uMash7SyYq527Hqck2AxYysAA7xmALppuCkwQ",
            "1JQheacLPdM5ySCkrZkV66G2ApAXe1mqLj"
        );

        $key3 = $key2->derivePath("2'");

        $this->checkFixture(
            $serializer,
            $key3,
            $addressCreator,
            $network,
            "xprv9z4pot5VBttmtdRTWfWQmoH1taj2axGVzFqSb8C9xaxKymcFzXBDptWmT7FwuEzG3ryjH4ktypQSAewRiNMjANTtpgP4mLTj34bhnZX7UiM",
            "xpub6D4BDPcP2GT577Vvch3R8wDkScZWzQzMMUm3PWbmWvVJrZwQY4VUNgqFJPMM3No2dFDFGTsxxpG5uJh7n7epu4trkrX7x7DogT5Uv6fcLW5",
            "1NjxqbA9aZWnh17q1UW3rB4EPu79wDXj7x"
        );
    }
}
