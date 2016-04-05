<?php

require "../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

Bitcoin::setNetwork(NetworkFactory::bitcoinSegnet());

$key = PrivateKeyFactory::fromWif('QP3p9tRpTGTefG4a8jKoktSWC7Um8qzvt8wGKMxwWyW3KTNxMxN7');
$scriptPubKey = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPublicKey());
echo $key->getPublicKey()->getAddress()->getAddress() . PHP_EOL;

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
$signed = (new Signer($tx,  Bitcoin::getEcAdapter()))
    ->sign(0, $key, $txOut)
    ->get();

echo $signed->getHex() . PHP_EOL;