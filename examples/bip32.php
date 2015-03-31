<?php

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Address;
use BitWasp\Bitcoin\Key\HierarchicalKeyFactory;

require __DIR__ . "/../vendor/autoload.php";

$math = Bitcoin::getMath();
$network = Bitcoin::getNetwork();

$master = HierarchicalKeyFactory::generateMasterKey();
echo $master->toExtendedPrivateKey($network) . "\n";
echo "Address: " . $master->getPublicKey()->getAddress() . "\n\n";

$key1 = $master->deriveChild($master->getHardenedSequence(0));
echo $key1->toExtendedPrivateKey($network) . "\n";
echo "Address: " . $key1->getPublicKey()->getAddress() . "\n\n";

$key2 = $key1->deriveChild(999999);
echo $key2->toExtendedPublicKey($network) . "\n";
echo "Address: " . $key2->getPublicKey()->getAddress() . "\n\n";