<?php

require __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeySequence;
use BitWasp\Bitcoin\Key\Deterministic\MultisigHD;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;

function status(MultisigHD $hd)
{
    echo "Path: " . $hd->getPath() . "\n";
    echo "Keys: \n";
    foreach ($hd->getKeys() as $key) {
        echo " - " . $key->toExtendedKey() . "\n";
    }
    echo "Script: " . $hd->getRedeemScript()->getScriptParser()->getHumanReadable() . "\n";
    echo "Address: " . $hd->getAddress()->getAddress() . "\n";
    echo "\n";
}

$ec = Bitcoin::getEcAdapter();

$bip39 = (new MnemonicFactory())->bip39();
$seed = new Bip39SeedGenerator();
$hdFactory = new HierarchicalKeyFactory();
$s = [];
$k = [];
for ($i = 0; $i < 3; $i++) {
    $s[$i] = $seed->getSeed($bip39->create());
    $k[$i] = $hdFactory->fromEntropy($s[$i]);
}

$sequences = new HierarchicalKeySequence();
$hd = new MultisigHD(2, 'm', $k, $sequences, true);

status($hd);
$new = $hd->derivePath("0/1h/2");

status($new);
