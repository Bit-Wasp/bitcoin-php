<?php

require_once __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;

// Generate a mnemonic
$random = new Random();
$entropy = $random->bytes(64);

$bip39 = MnemonicFactory::bip39();
$seedGenerator = new Bip39SeedGenerator($bip39);
$mnemonic = $bip39->entropyToMnemonic($entropy);

// Derive a seed from mnemonic/password
$seed = $seedGenerator->getSeed($mnemonic, 'password');
echo $seed->getHex() . "\n";

$bip32 = \BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory::fromEntropy($seed);
