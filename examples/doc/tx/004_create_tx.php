<?php

require __DIR__ . "/../../../vendor/autoload.php";

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionOutput;

/**
 * This example reconstructs a transaction object from
 * information about the transaction input & outputs,
 * before echoing its hash:
 * 5c27196fc96f0db5499ee3c78466b4920c2ab13463793d3d32015ce32289131a
 */

$input = new TransactionInput(
    new OutPoint(
        Buffer::hex('e25b7fc1f9f04982620e1264e390fdf5a4ab36c9bbbd54ce06ca4e4887ad1c40'),
        1
    ),
    new Script(Buffer::hex("483045022100d94b993d74c44439e3c83c6c6dd2a5a6d9765a3cb61c89fd7a2dc3e5d4e6a36b0220716f91456bf730eb0eddfa5f88ec5898f3fefcb311c688efacb728ff816c4ff1012102fbe61846a939936af3f8b05dbe237b9eddb9038fb195cc77433c28623c967abf"))
);

$txOut1 = new TransactionOutput(
    43960,
    new Script(Buffer::hex("76a914c05c2fd72090b06042377598c63af30d13a713a388ac"))
);

$txOut2 = new TransactionOutput(
    499410,
    new Script(Buffer::hex("76a914b5a8a683e0f4f92fa2dc611b6d789cab964a104f88ac"))
);

$version = 1;
$txIns = [$input];
$txOuts = [$txOut1, $txOut2];
$witnesses = [];
$locktime = 0;

$transaction = new Transaction(
    $version,
    $txIns,
    $txOuts,
    $witnesses,
    0 /* locktime */
);

echo $transaction->getTxId()->getHex().PHP_EOL;
