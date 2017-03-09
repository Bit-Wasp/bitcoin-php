<?php

require __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Script\WitnessScript;

// Setup network and private key to segnet
Bitcoin::setNetwork(NetworkFactory::bitcoinSegnet());
$key = PrivateKeyFactory::fromWif('QP3p9tRpTGTefG4a8jKoktSWC7Um8qzvt8wGKMxwWyW3KTNxMxN7');

// Spend from P2PKH
$scriptPubKey = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPubKeyHash());

// UTXO
$outpoint = new OutPoint(Buffer::hex('499c2bff1499bf84bc63058fda3ed112c2c663389f108353798a1bd6a6651afe', 32), 0);
$txOut = new TransactionOutput(100000000, $scriptPubKey);

// Move funds into P2WSH P2PKH
$destination = new WitnessScript($scriptPubKey);

// Create unsigned transaction
$tx = (new TxBuilder())
    ->spendOutPoint($outpoint)
    ->output(99990000, $destination->getOutputScript())
    ->get();

// Sign trasaction
$signed = (new Signer($tx, Bitcoin::getEcAdapter()))
    ->sign(0, $key, $txOut)
    ->get();

echo $signed->getBuffer()->getHex() . PHP_EOL;
