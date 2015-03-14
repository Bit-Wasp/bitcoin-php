<?php

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Address;
use Afk11\Bitcoin\Key\PrivateKeyFactory;
use Afk11\Bitcoin\Network\Network;

require __DIR__ . "/../vendor/autoload.php";

$network = Bitcoin::getNetwork();
$makeAddress = new Address($network);

$privateKey = PrivateKeyFactory::create(true);
$publicKey = $privateKey->getPublicKey();
$address = $makeAddress->fromKey($publicKey);

echo "Key Info\n";
echo " - Compressed? " . (($privateKey->isCompressed() ? 'yes' : 'no')) . "\n";

echo "Private key\n";
echo " - WIF: " . $privateKey->toWif($network) . "\n";
echo " - Hex: " . $privateKey->getBuffer() . "\n";
echo " - Dec: " . $privateKey->getSecretMultiplier() . "\n";

echo "Public Key\n";
echo " - Hex: " . $publicKey->getBuffer() . "\n";
echo " - Hash: " . $publicKey->getPubKeyHash() . "\n";
echo " - Address: " . $address . "\n";


