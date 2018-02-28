<?php

namespace BitWasp\Bitcoin\Tests\Key\Deterministic;

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2shScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2wpkhScriptDataFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class Bip49Test extends AbstractTestCase
{
    public function testBip49()
    {
        $adapter = Bitcoin::getEcAdapter();
        $tbtc = NetworkFactory::bitcoinTestnet();

        $bip39 = new Bip39SeedGenerator();
        $ent = $bip39->getSeed("abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about");
        $root = HierarchicalKeyFactory::fromEntropy($ent, $adapter);
        $this->assertEquals(
            "tprv8ZgxMBicQKsPe5YMU9gHen4Ez3ApihUfykaqUorj9t6FDqy3nP6eoXiAo2ssvpAjoLroQxHqr3R5nE3a5dU3DHTjTgJDd7zrbniJr6nrCzd",
            $root->toExtendedPrivateKey($tbtc)
        );

        $account = $root->derivePath("49'/1'/0'");
        $account0Xpriv = "tprv8gRrNu65W2Msef2BdBSUgFdRTGzC8EwVXnV7UGS3faeXtuMVtGfEdidVeGbThs4ELEoayCAzZQ4uUji9DUiAs7erdVskqju7hrBcDvDsdbY";
        $this->assertEquals($account0Xpriv, $account->toExtendedPrivateKey($tbtc));

        $firstKey = $account->derivePath("0/0");
        $firstWif = "cULrpoZGXiuC19Uhvykx7NugygA3k86b3hmdCeyvHYQZSxojGyXJ";
        $firstPublicKey = "03a1af804ac108a8a51782198c2d034b28bf90c8803f5a53f76276fa69a4eae77f";

        $this->assertEquals($firstWif, $firstKey->getPrivateKey()->toWif($tbtc));
        $this->assertEquals($firstPublicKey, $firstKey->getPrivateKey()->getPublicKey()->getHex());

        $decorated = new HierarchicalKeyScriptDecorator(
            new P2shScriptDecorator(new P2wpkhScriptDataFactory()),
            $firstKey
        );

        $firstAddress = "2Mww8dCYPUpKHofjgcXcBCEGmniw9CoaiD2";
        $address = $decorated->getAddress(new AddressCreator());
        $this->assertEquals($firstAddress, $address->getAddress($tbtc));
    }
}
