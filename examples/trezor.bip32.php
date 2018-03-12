<?php

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Address\BaseAddressCreator;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptFactory;

require __DIR__ . "/../vendor/autoload.php";

function toAddress(HierarchicalKey $key, BaseAddressCreator $addressCreator, $purpose)
{
    switch ($purpose) {
        case 44:
            $script = ScriptFactory::scriptPubKey()->p2pkh($key->getPublicKey()->getPubKeyHash());
            break;
        case 49:
            $rs = new P2shScript(ScriptFactory::scriptPubKey()->p2wkh($key->getPublicKey()->getPubKeyHash()));
            $script = $rs->getOutputScript();
            break;
        default:
            throw new \InvalidArgumentException("Invalid purpose");
    }
    return $addressCreator->fromOutputScript($script);
}

$mnemonic = "rain enhance term seminar upper must gun uniform huge brown fresh gun warrior mesh tag";

$bip39 = new Bip39SeedGenerator();
$seed = $bip39->getSeed($mnemonic);

$purpose = 44;

$root = HierarchicalKeyFactory::fromEntropy($seed);
echo "Root key (m): " . $root->toExtendedKey() . PHP_EOL;
echo "Root key (M): " . $root->toExtendedPublicKey() . PHP_EOL;

echo "\n\n -------------- \n\n";

echo "Derive (m -> m/{$purpose}'/0'/0'): \n";
$purposePriv = $root->derivePath("{$purpose}'/0'/0'");
echo "m/{$purpose}'/0'/0': ".$purposePriv->toExtendedPrivateKey().PHP_EOL;
echo "M/{$purpose}'/0'/0': ".$purposePriv->toExtendedPublicKey().PHP_EOL;

echo "Derive (M -> m/{$purpose}'/0'/0'): .... should fail\n";

try {
    $rootPub = $root->withoutPrivateKey();
    $rootPub->derivePath("{$purpose}'/0'/0'");
} catch (\Exception $e) {
    echo "caught exception, yes this is impossible: " . $e->getMessage().PHP_EOL;
}
$purposePub = $purposePriv->toExtendedPublicKey();

echo "\n\n -------------- \n\n";

echo "initialize from xpub (M/{$purpose}'/0'/0'): \n";

$xpub = HierarchicalKeyFactory::fromExtended($purposePub);
$addressCreator = new AddressCreator();

echo "0/0: ".toAddress($xpub->derivePath("0/0"), $addressCreator, $purpose)->getAddress().PHP_EOL;
echo "0/1: ".toAddress($xpub->derivePath("0/1"), $addressCreator, $purpose)->getAddress().PHP_EOL;
