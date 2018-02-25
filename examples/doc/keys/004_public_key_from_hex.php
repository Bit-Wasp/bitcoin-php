<?php

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\SegwitAddress;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;

require __DIR__ . "/../../../vendor/autoload.php";

$pubKeyFactory = new PublicKeyFactory();
$publicKey = $pubKeyFactory->fromHex("0391935b9b79e864ea4c352f19c5c565e8f27f1c364d1e9be04ac2dd05edb80a9a");
echo "public key  hex  {$publicKey->getHex()}\n";

$pubKeyHash160 = $publicKey->getPubKeyHash();
$pubKeyHashAddr = new PayToPubKeyHashAddress($pubKeyHash160);
echo "p2pkh address    {$pubKeyHashAddr->getAddress()}\n";

$witnessPubKeyHashAddr = new SegwitAddress(WitnessProgram::v0($pubKeyHash160));
echo "p2wpkh address   {$witnessPubKeyHashAddr->getAddress()}\n";
