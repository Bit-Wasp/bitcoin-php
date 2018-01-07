<?php

require __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

$addrCreator = new AddressCreator();
$transaction = TransactionFactory::build()
    ->input('99fe5212e4e52e2d7b35ec0098ae37881a7adaf889a7d46683d3fbb473234c28', 0)
    ->payToAddress(29890000, $addrCreator->fromString('19SokJG7fgk8iTjemJ2obfMj14FM16nqzj'))
    ->payToAddress(100000, $addrCreator->fromString('1CzcTWMAgBNdU7K8Bjj6s6ezm2dAQfUU9a'))
    ->get();

echo $transaction->getHex() . PHP_EOL;
