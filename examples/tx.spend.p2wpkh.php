<?php

require __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface as I;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

Bitcoin::setNetwork(NetworkFactory::bitcoinSegnet());

$key = PrivateKeyFactory::fromWif('QP3p9tRpTGTefG4a8jKoktSWC7Um8qzvt8wGKMxwWyW3KTNxMxN7');

// scriptPubKey is P2WKH
$program = new WitnessProgram(0, $key->getPubKeyHash());

// UTXO
$outpoint = new OutPoint(Buffer::hex('3a4242c32cf9dca64df73450c7a6141840538b90ccf5d5206b3482e52f7486fc', 32), 0);
$txOut = new TransactionOutput(99900000, $program->getScript());

// Create unsigned transaction
$tx = (new TxBuilder())
    ->spendOutPoint($outpoint)
    ->payToAddress(99850000, $key->getPublicKey()->getAddress())
    ->get();

// Sign transaction
$signer = new Signer($tx);
$input = $signer->input(0, $txOut);
$input->sign($key);
$signed = $signer->get();

// Check our signature is correct
echo "Script validation result: " . ($input->verify(I::VERIFY_P2SH | I::VERIFY_WITNESS) ? "yay\n" : "nay\n");

echo PHP_EOL;
echo "Witness serialized transaction: " . $signed->getWitnessBuffer()->getHex() . PHP_EOL. PHP_EOL;
echo "Base serialized transaction: " . $signed->getHex() . PHP_EOL;
