<?php

use BitWasp\Bitcoin\Base58;

require __DIR__ . "/../vendor/autoload.php";

if (!isset($argv[1])) {
    die('Must provide base58 data to decode');
}

$data = $argv[1];;
try {
    $text = Base58::decode($data)->getHex();
} catch (\Exception $e) {
    $text = 'Invalid base58 data';
}

echo $text . PHP_EOL;
