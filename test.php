<?php

use Bitcoin\Math;
use Bitcoin\Buffer;
use Bitcoin\Network;
use Bitcoin\Transaction;

require_once "vendor/autoload.php";

echo Math::add(1,2);
echo "\n";
echo Buffer::hex('4141');
echo "\n";

$bitcoin = new Network('00','05','80');
$tx = new Transaction($bitcoin);

$input = new \Bitcoin\TransactionInput();
$input
    ->setTransactionId('0000000000000000000000000000000000000000000000000000000000000000')
    ->setVout('0');

$tx->addInput($input);
print_r($tx);