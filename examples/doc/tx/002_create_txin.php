<?php

require __DIR__ . "/../../../vendor/autoload.php";

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\TransactionInput;

$outpoint = new OutPoint(Buffer::hex('0000000000000000000000000000000000000000000000000000000000000000'), 0xffffffff);
$sequence = TransactionInput::SEQUENCE_FINAL;
$script = new Script(Buffer::hex("0313bc07272f706f6f6c2e626974636f696e2e636f6d2f4542312f41443939392f464732403439343738342f10020dc800341e8aeebff64bf93a2aa600"));

$input = new TransactionInput($outpoint, $script, $sequence);

echo "txid: {$input->getOutPoint()->getTxId()->getHex()}\n";
echo "vout: {$input->getOutPoint()->getVout()}\n";
echo "script: {$input->getScript()->getHex()}\n";
echo "sequence: {$input->getSequence()}\n";
echo "isCoinbase: " . ($input->isCoinbase() ? "yes" : "no") . "\n";
