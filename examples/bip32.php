<?php

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;

require __DIR__ . "/../vendor/autoload.php";

$math = Bitcoin::getMath();
$network = Bitcoin::getNetwork();
$random = new Random();

// By default, this example produces random keys.
$hdFactory = new HierarchicalKeyFactory();
$master = $hdFactory->generateMasterKey($random);

// To restore from an existing xprv/xpub:
//$master = $hdFactory->fromExtended("xprv9s21ZrQH143K4Se1mR27QkNkLS9LSarRVFQcopi2mcomwNPDaABdM1gjyow2VgrVGSYReepENPKX2qiH61CbixpYuSg4fFgmrRtk6TufhPU");
echo "Master key (m)\n";
echo "   " . $master->toExtendedPrivateKey($network) . "\n";
;
$masterAddr = new PayToPubKeyHashAddress($master->getPublicKey()->getPubKeyHash());

echo "   Address: " . $masterAddr->getAddress() . "\n\n";

echo "UNHARDENED PATH\n";
echo "Derive sequential keys:\n";
$key1 = $master->deriveChild(0);
echo " - m/0 " . $key1->toExtendedPrivateKey($network) . "\n";

$child1 = new PayToPubKeyHashAddress($key1->getPublicKey()->getPubKeyHash());
echo "   Address: " . $child1->getAddress() . "\n\n";

$key2 = $key1->deriveChild(999999);
echo " - m/0/999999 " . $key2->toExtendedPublicKey($network) . "\n";


$child2 = new PayToPubKeyHashAddress($key2->getPublicKey()->getPubKeyHash());
echo "   Address: " . $child2->getAddress() . "\n\n";

echo "Directly derive path\n";

$sameKey2 = $master->derivePath("0/999999");
echo " - m/0/999999 " . $sameKey2->toExtendedPublicKey() . "\n";

$child3 = new PayToPubKeyHashAddress($sameKey2->getPublicKey()->getPubKeyHash());
echo "   Address: " . $child3->getAddress() . "\n\n";

echo "HARDENED PATH\n";
$hardened2 = $master->derivePath("0/999999'");

$child4 = new PayToPubKeyHashAddress($hardened2->getPublicKey()->getPubKeyHash());
echo " - m/0/999999' " . $hardened2->toExtendedPublicKey() . "\n";
echo "   Address: " . $child4->getAddress() . "\n\n";
