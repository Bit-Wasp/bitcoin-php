<?php

require __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

// Setup network and private key to segnet
$privKeyFactory = PrivateKeyFactory::compressed();
$key = $privKeyFactory->fromHex("4242424242424242424242424242424242424242424242424242424242424242");

$scriptPubKey = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPubKeyHash());

// Utxo
$outpoint = new OutPoint(Buffer::hex('874381bb431eaaae16e94f8b88e4ea7baf2ebf541b2ae11ec10d54c8e03a237f', 32), 0);
$txOut = new TransactionOutput(100000000, $scriptPubKey);

// Destination is a pay-to-witness pubkey-hash
$p2wpkh = new WitnessProgram(0, $key->getPubKeyHash());

// Create unsigned transaction, spending UTXO, moving funds to P2WPKH
$tx = (new TxBuilder())
    ->spendOutPoint($outpoint)
    ->output(99900000, $p2wpkh->getScript())
    ->get();

// Sign transaction
$signed = (new Signer($tx, Bitcoin::getEcAdapter()))
    ->sign(0, $key, $txOut)
    ->get();

echo $signed->getHex() . PHP_EOL;
