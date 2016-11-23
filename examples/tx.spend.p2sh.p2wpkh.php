<?php

require __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Transaction\Factory\SignData;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface as I;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

Bitcoin::setNetwork(NetworkFactory::bitcoinSegnet());

$key = PrivateKeyFactory::fromWif('QP3p9tRpTGTefG4a8jKoktSWC7Um8qzvt8wGKMxwWyW3KTNxMxN7');

// scriptPubKey is P2SH | P2WPKH
$destination = new WitnessProgram(0, $key->getPubKeyHash());
$p2sh = new P2shScript($destination->getScript());

// UTXO
$outpoint = new OutPoint(Buffer::hex('23d6640c3f3383ffc8233fbd830ee49162c720389bbba1c313a43b06a235ae13', 32), 0);
$txOut = new TransactionOutput(95590000, $p2sh->getOutputScript());

// Move UTXO to a pub-key-hash address
$tx = (new TxBuilder())
    ->spendOutPoint($outpoint)
    ->payToAddress(94550000, $key->getPublicKey()->getAddress())
    ->get();

// Sign transaction
$signData = (new SignData())->p2sh($destination->getScript());
$signer = new Signer($tx);
$input = $signer->input(0, $txOut, $signData);
$input->sign($key);
$signed = $signer->get();

$consensus = ScriptFactory::consensus();
$flags = I::VERIFY_P2SH | I::VERIFY_WITNESS;
echo "Script validation result: " . ($input->verify() ? "yay\n" : "nay\n");

echo PHP_EOL;
echo "Witness serialized transaction: " . $signed->getWitnessBuffer()->getHex() . PHP_EOL. PHP_EOL;
echo "Base serialized transaction: " . $signed->getHex() . PHP_EOL;
