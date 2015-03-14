<?php

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Address;
use Afk11\Bitcoin\Key\HierarchicalKeyFactory;
use Afk11\Bitcoin\Key\HierarchicalKey;
use Afk11\Bitcoin\Network\Network;

require __DIR__ . "/../vendor/autoload.php";

$math = Bitcoin::getMath();
$network = Bitcoin::getNetwork();
$makeAddress = new Address($network);

$master = HierarchicalKeyFactory::generateMasterKey();
echo $master->toExtendedPrivateKey($network)."\n";
echo "Address: " . $makeAddress->fromKey($master) . "\n\n";

$key1 = $master->deriveChild(HierarchicalKey::hardenedSequence($math, 0));
echo $key1->toExtendedPrivateKey($network)."\n";
echo "Address: " . $makeAddress->fromKey($key1) . "\n\n";

$key2 = $key1->deriveChild(999999);
echo $key2->toExtendedPublicKey($network)."\n";
echo "Address: " . $makeAddress->fromKey($key2) . "\n\n";