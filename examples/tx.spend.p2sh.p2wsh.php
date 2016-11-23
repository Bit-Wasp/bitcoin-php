<?php

require __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface as I;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Transaction\Factory\SignData;

Bitcoin::setNetwork(NetworkFactory::bitcoinSegnet());

$key = PrivateKeyFactory::fromWif('QP3p9tRpTGTefG4a8jKoktSWC7Um8qzvt8wGKMxwWyW3KTNxMxN7');

// Script is P2SH | P2WSH | P2PKH
$witnessScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPubKeyHash());
$p2shScript = ScriptFactory::scriptPubKey()->witnessScriptHash(Hash::sha256($witnessScript->getBuffer()));
$scriptPubKey = ScriptFactory::scriptPubKey()->payToScriptHash($p2shScript->getScriptHash());

$signData = (new SignData())
    ->p2sh($p2shScript)
    ->p2wsh($witnessScript);

// Utxo
$outpoint = new OutPoint(Buffer::hex('5df04c88810066136619ce715ae9350113b0d4157f5b40ea860204b481bb0cc9', 32), 0);
$txOut = new TransactionOutput(95590000, $scriptPubKey);

// Move UTXO to pub-key-hash
$tx = (new TxBuilder())
    ->spendOutPoint($outpoint)
    ->payToAddress(94550000, $key->getPublicKey()->getAddress())
    ->get();

// Sign the transaction
$signer = (new Signer($tx, Bitcoin::getEcAdapter()));
$input = $signer->input(0, $txOut, $signData);
$input->sign($key);
$signed = $signer->get();

// Verify what we've produced

$consensus = ScriptFactory::consensus();
echo "Script validation result: " . ($input->verify(I::VERIFY_P2SH | I::VERIFY_WITNESS) ? "yay\n" : "nay\n");

echo PHP_EOL;
echo "Witness serialized transaction: " . $signed->getWitnessBuffer()->getHex() . PHP_EOL. PHP_EOL;
echo "Base serialized transaction: " . $signed->getHex() . PHP_EOL;
