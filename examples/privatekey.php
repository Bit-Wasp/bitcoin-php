<?php

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Address;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;

require __DIR__ . "/../vendor/autoload.php";

$network = Bitcoin::getNetwork();


$privateKey = PrivateKeyFactory::create(true);
$publicKey = $privateKey->getPublicKey();

echo "Key Info\n";
echo " - Compressed? " . (($privateKey->isCompressed() ? 'yes' : 'no')) . "\n";

echo "Private key\n";
echo " - WIF: " . $privateKey->toWif($network) . "\n";
echo " - Hex: " . $privateKey->getBuffer() . "\n";
echo " - Dec: " . $privateKey->getSecretMultiplier() . "\n";

echo "Public Key\n";
echo " - Hex: " . $publicKey->getBuffer() . "\n";
echo " - Hash: " . $publicKey->getPubKeyHash() . "\n";
echo " - Address: " . $publicKey->getAddress() . "\n";


