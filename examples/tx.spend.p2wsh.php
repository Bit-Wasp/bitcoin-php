<?php

require "../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface as I;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

Bitcoin::setNetwork(NetworkFactory::bitcoinSegnet());

$key = PrivateKeyFactory::fromWif('QP3p9tRpTGTefG4a8jKoktSWC7Um8qzvt8wGKMxwWyW3KTNxMxN7');

// scriptPubKey is P2WSH | P2PKH
$destScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPublicKey());
$program = new WitnessProgram(0, Hash::sha256($destScript->getBuffer()));

// UTXO
$outpoint = new OutPoint(Buffer::hex('c2197f15d510304f1463230c0e61566bfb8dcadb7e1c510d3c0470bcfbca2194', 32), 0);
$txOut = new TransactionOutput(99990000, $program->getScript());

// Create unsigned transaction
$tx = (new TxBuilder())
    ->spendOutPoint($outpoint)
    ->payToAddress(97900000, $key->getPublicKey()->getAddress())
    ->get();

// Sign
$signed = (new Signer($tx, Bitcoin::getEcAdapter()))
    ->sign(0, $key, $txOut, null, $destScript)
    ->get();

// Check signatures
$consensus = ScriptFactory::consensus(I::VERIFY_P2SH | I::VERIFY_WITNESS);
echo "Script validation result: " . ($signed->validator()->checkSignature($consensus, 0, $txOut) ? "yay\n" : "nay\n");

echo PHP_EOL;
echo "Witness serialized transaction: " . $signed->getWitnessBuffer()->getHex() . PHP_EOL. PHP_EOL;
echo "Base serialized transaction: " . $signed->getHex() . PHP_EOL;