<?php

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;

require __DIR__ . "/../vendor/autoload.php";

$math = Bitcoin::getMath();
$network = Bitcoin::getNetwork();

// By default, this example produces random keys.
$master = HierarchicalKeyFactory::generateMasterKey();

// To restore from an existing xprv/xpub:
//$master = HierarchicalKeyFactory::fromExtended("xprv9s21ZrQH143K4Se1mR27QkNkLS9LSarRVFQcopi2mcomwNPDaABdM1gjyow2VgrVGSYReepENPKX2qiH61CbixpYuSg4fFgmrRtk6TufhPU");
echo "Master key (m)\n";
echo "   " . $master->toExtendedPrivateKey($network) . "\n";
$masterAddr = AddressFactory::p2pkh($master->getPublicKey());
echo "   Address: " . $masterAddr->getAddress() . "\n\n";

echo "UNHARDENED PATH\n";
echo "Derive sequential keys:\n";
$key1 = $master->deriveChild(0);
echo " - m/0 " . $key1->toExtendedPrivateKey($network) . "\n";
$child1 = AddressFactory::p2pkh($key1->getPublicKey());
echo "   Address: " . $child1->getAddress() . "\n\n";

$key2 = $key1->deriveChild(999999);
echo " - m/0/999999 " . $key2->toExtendedPublicKey($network) . "\n";
$child2 = AddressFactory::p2pkh($key2->getPublicKey());
echo "   Address: " . $child2->getAddress() . "\n\n";

echo "Directly derive path\n";

$sameKey2 = $master->derivePath("0/999999");
echo " - m/0/999999 " . $sameKey2->toExtendedPublicKey() . "\n";
$child3 = AddressFactory::p2pkh($sameKey2->getPublicKey());
echo "   Address: " . $child3->getAddress() . "\n\n";

echo "HARDENED PATH\n";
$hardened2 = $master->derivePath("0/999999'");
$child4 = AddressFactory::p2pkh($hardened2->getPublicKey());
echo " - m/0/999999' " . $hardened2->toExtendedPublicKey() . "\n";
echo "   Address: " . $child4->getAddress() . "\n\n";
