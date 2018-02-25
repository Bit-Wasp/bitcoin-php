<?php

require __DIR__ . "/../../../vendor/autoload.php";

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\SegwitAddress;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;

$privKeyFactory = new PrivateKeyFactory(true);

$rbg = new Random();
$privateKey = $privKeyFactory->fromWif("L2uRfwpmG3RTXTy6ZvzTRC4Xtkwi6axoQFopgRsCEpsSc5Qh5uSP");
$publicKey = $privateKey->getPublicKey();
echo "private key wif  {$privateKey->toWif()}\n";
echo "            hex  {$privateKey->getHex()}\n";
echo "compressed       ".($privateKey->isCompressed()?"true":"false").PHP_EOL;
echo "public key  hex  {$publicKey->getHex()}\n";

$pubKeyHash160 = $publicKey->getPubKeyHash();
$pubKeyHashAddr = new PayToPubKeyHashAddress($pubKeyHash160);
echo "p2pkh address    {$pubKeyHashAddr->getAddress()}\n";

$witnessPubKeyHashAddr = new SegwitAddress(WitnessProgram::v0($pubKeyHash160));
echo "p2wpkh address   {$witnessPubKeyHashAddr->getAddress()}\n";
