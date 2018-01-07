<?php

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;

require __DIR__ . "/../vendor/autoload.php";

$network = Bitcoin::getNetwork();

$privateKey = PrivateKeyFactory::create(true);
$publicKey = $privateKey->getPublicKey();

echo "Key Info\n";
echo " - Compressed? " . (($privateKey->isCompressed() ? 'yes' : 'no')) . "\n";

echo "Private key\n";
echo " - WIF: " . $privateKey->toWif($network) . "\n";
echo " - Hex: " . $privateKey->getHex() . "\n";
echo " - Dec: " . gmp_strval($privateKey->getSecret(), 10) . "\n";

echo "Public Key\n";
echo " - Hex: " . $publicKey->getHex() . "\n";
echo " - Hash: " . $publicKey->getPubKeyHash()->getHex() . "\n";

$address = new PayToPubKeyHashAddress($publicKey->getPubKeyHash());
echo " - Address: " . $address->getAddress() . "\n";
