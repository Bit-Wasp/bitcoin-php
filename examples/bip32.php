<?php

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Address;
use BitWasp\Bitcoin\Key\HierarchicalKeyFactory;

require __DIR__ . "/../vendor/autoload.php";

$math = Bitcoin::getMath();
$network = Bitcoin::getNetwork();
$makeAddress = new Address($network);

$master = HierarchicalKeyFactory::generateMasterKey();
echo $master->toExtendedPrivateKey($network) . "\n";
echo "Address: " . $makeAddress->fromKey($master) . "\n\n";

$key1 = $master->deriveChild($master->getHardenedSequence(0));
echo $key1->toExtendedPrivateKey($network) . "\n";
echo "Address: " . $makeAddress->fromKey($key1) . "\n\n";

$key2 = $key1->deriveChild(999999);
echo $key2->toExtendedPublicKey($network) . "\n";
echo "Address: " . $makeAddress->fromKey($key2) . "\n\n";