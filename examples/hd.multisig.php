<?php

require "../vendor/autoload.php";

use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;

function status(\BitWasp\Bitcoin\Key\Deterministic\MultisigHD $hd)
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

$ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();

$bip39 = (new MnemonicFactory())->bip39();
$seed = new Bip39SeedGenerator($bip39);

$s = [];
$k = [];
for ($i = 0; $i < 3; $i++) {
    $s[$i] = $seed->getSeed($bip39->create());
    $k[$i] = \BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory::fromEntropy($s[$i]);
}

$sequences = new \BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeySequence($ec->getMath());
$hd = new \BitWasp\Bitcoin\Key\Deterministic\MultisigHD(2, 'm', $k, $sequences, true);

status($hd);
$new = $hd->derivePath("0/1h/2");

status($new);