<?php

namespace BitWasp\Bitcoin\Tests\Key\Deterministic;

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Deterministic\ScriptedHierarchicalKeyFactory;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2wpkhScriptDataFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey\Base58ScriptedExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey\ExtendedKeyWithScriptSerializer;
use BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey\GlobalHdKeyPrefixConfig;
use BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey\NetworkHdKeyPrefixConfig;
use BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey\NetworkScriptPrefix;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class Bip84Test extends AbstractTestCase
{
    public function testBip84()
    {
        $adapter = Bitcoin::getEcAdapter();
        $btc = NetworkFactory::bitcoin();

        $p2wpkhScriptDataFactory = new P2wpkhScriptDataFactory();

        $btcConfig = new NetworkHdKeyPrefixConfig($btc, [
            new NetworkScriptPrefix($p2wpkhScriptDataFactory, "04b2430c", "04b24746"),
        ]);

        $globalConfig = new GlobalHdKeyPrefixConfig([$btcConfig]);
        $ser = new Base58ScriptedExtendedKeySerializer(
            new ExtendedKeyWithScriptSerializer($adapter, $globalConfig)
        );

        $bip39 = new Bip39SeedGenerator();
        $ent = $bip39->getSeed("abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about");

        $addrFactory = new AddressCreator();
        $rootzprv = "zprvAWgYBBk7JR8Gjrh4UJQ2uJdG1r3WNRRfURiABBE3RvMXYSrRJL62XuezvGdPvG6GFBZduosCc1YP5wixPox7zhZLfiUm8aunE96BBa4Kei5";
        $rootPriv = ScriptedHierarchicalKeyFactory::fromEntropy($ent, $p2wpkhScriptDataFactory);
        $this->assertEquals($rootzprv, $ser->serialize($btc, $rootPriv));

        $rootzpub = "zpub6jftahH18ngZxLmXaKw3GSZzZsszmt9WqedkyZdezFtWRFBZqsQH5hyUmb4pCEeZGmVfQuP5bedXTB8is6fTv19U1GQRyQUKQGUTzyHACMF";

        $rootPub = $rootPriv->withoutPrivateKey();
        $this->assertEquals($rootzpub, $ser->serialize($btc, $rootPub));

        $xpriv = "zprvAdG4iTXWBoARxkkzNpNh8r6Qag3irQB8PzEMkAFeTRXxHpbF9z4QgEvBRmfvqWvGp42t42nvgGpNgYSJA9iefm1yYNZKEm7z6qUWCroSQnE";
        $xprivKey = $rootPriv->derivePath("84'/0'/0'");
        $this->assertEquals($xpriv, $ser->serialize($btc, $xprivKey));

        $xpub = "zpub6rFR7y4Q2AijBEqTUquhVz398htDFrtymD9xYYfG1m4wAcvPhXNfE3EfH1r1ADqtfSdVCToUG868RvUUkgDKf31mGDtKsAYz2oz2AGutZYs";
        $xpubKey = $xprivKey->withoutPrivateKey();
        $this->assertEquals($xpub, $ser->serialize($btc, $xpubKey));

        $account0_0_prv = $xprivKey->derivePath("0/0");
        $this->assertEquals(
            "KyZpNDKnfs94vbrwhJneDi77V6jF64PWPF8x5cdJb8ifgg2DUc9d",
            $account0_0_prv->getHdKey()->getPrivateKey()->toWif()
        );
        $this->assertEquals(
            "0330d54fd0dd420a6e5f8d3624f5f3482cae350f79d5f0753bf5beef9c2d91af3c",
            $account0_0_prv->getHdKey()->getPublicKey()->getHex()
        );
        $this->assertEquals(
            "bc1qcr8te4kr609gcawutmrza0j4xv80jy8z306fyu",
            $account0_0_prv->getAddress($addrFactory)->getAddress()
        );

        $account0_1_prv = $xprivKey->derivePath("0/1");
        $this->assertEquals(
            "Kxpf5b8p3qX56DKEe5NqWbNUP9MnqoRFzZwHRtsFqhzuvUJsYZCy",
            $account0_1_prv->getHdKey()->getPrivateKey()->toWif()
        );
        $this->assertEquals(
            "03e775fd51f0dfb8cd865d9ff1cca2a158cf651fe997fdc9fee9c1d3b5e995ea77",
            $account0_1_prv->getHdKey()->getPublicKey()->getHex()
        );
        $this->assertEquals(
            "bc1qnjg0jd8228aq7egyzacy8cys3knf9xvrerkf9g",
            $account0_1_prv->getAddress($addrFactory)->getAddress()
        );

        $account1_0_prv = $xprivKey->derivePath("1/0");
        $this->assertEquals(
            "KxuoxufJL5csa1Wieb2kp29VNdn92Us8CoaUG3aGtPtcF3AzeXvF",
            $account1_0_prv->getHdKey()->getPrivateKey()->toWif()
        );
        $this->assertEquals(
            "03025324888e429ab8e3dbaf1f7802648b9cd01e9b418485c5fa4c1b9b5700e1a6",
            $account1_0_prv->getHdKey()->getPublicKey()->getHex()
        );
        $this->assertEquals(
            "bc1q8c6fshw2dlwun7ekn9qwf37cu2rn755upcp6el",
            $account1_0_prv->getAddress($addrFactory)->getAddress()
        );
    }
}
