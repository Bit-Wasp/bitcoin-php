<?php

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Address\Base58AddressInterface;
use BitWasp\Bitcoin\Address\SegwitAddress;

require __DIR__ . "/../vendor/autoload.php";

// call \BitWasp\Bitcoin\Bitcoin::setNetwork to set network globally
// or pass it into getHRP, getPrefix

//$addressString = "bc1qwqdg6squsna38e46795at95yu9atm8azzmyvckulcc7kytlcckxswvvzej";
$addressString = "3BbDtxBSjgfTRxaBUgR2JACWRukLKtZdiQ";

$addrCreator = new AddressCreator();
$address = $addrCreator->fromString($addressString);

if ($address instanceof Base58AddressInterface) {
    echo "Base58 Hash160: " . $address->getHash()->getHex().PHP_EOL;
} else if ($address instanceof SegwitAddress) {
    $witnessProgram = $address->getWitnessProgram();
    echo "HRP: " . $address->getHRP().PHP_EOL;
    echo "WP Version: " . $witnessProgram->getVersion().PHP_EOL;
    echo "WP Program: " . $witnessProgram->getProgram()->getHex().PHP_EOL;
    echo "Addr Program: " . $address->getHash()->getHex().PHP_EOL;
}

echo "ScriptPubKey: " . $address->getScriptPubKey()->getHex().PHP_EOL;
