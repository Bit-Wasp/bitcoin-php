<?php

require "../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
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

// Script is P2SH | P2WSH | P2PKH
$scriptPubKey = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPublicKey());
$program = new WitnessProgram(0, Hash::sha256($scriptPubKey->getBuffer()));
$p2sh = new P2shScript($program->getScript());

// Utxo
$outpoint = new OutPoint(Buffer::hex('5df04c88810066136619ce715ae9350113b0d4157f5b40ea860204b481bb0cc9', 32), 0);
$txOut = new TransactionOutput(95590000, $p2sh->getOutputScript());

// Move UTXO to pub-key-hash
$tx = (new TxBuilder())
    ->spendOutPoint($outpoint)
    ->payToAddress(94550000, $key->getPublicKey()->getAddress())
    ->get();

// Sign the transaction
$signed = (new Signer($tx, Bitcoin::getEcAdapter()))
    ->sign(0, $key, $txOut, $program->getScript(), $scriptPubKey)
    ->get();

// Verify what we've produced
$consensus = ScriptFactory::consensus(I::VERIFY_P2SH | I::VERIFY_WITNESS);
echo "Script validation result: " . ($signed->validator()->checkSignature($consensus, 0, $txOut) ? "yay\n" : "nay\n");

echo PHP_EOL;
echo "Witness serialized transaction: " . $signed->getWitnessBuffer()->getHex() . PHP_EOL. PHP_EOL;
echo "Base serialized transaction: " . $signed->getHex() . PHP_EOL;