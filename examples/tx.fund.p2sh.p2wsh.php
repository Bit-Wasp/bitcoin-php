<?php

require __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\WitnessScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
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
$outpoint = new OutPoint(Buffer::hex('703f50920bff10e1622117af81b622d8bbd625460e61909cc3f8b8ee78a59c0d', 32), 0);
$txOut = new TransactionOutput(100000000, $scriptPubKey);

// Script is P2SH | P2WSH | P2PKH
$p2wsh = new WitnessScript($scriptPubKey);
$p2sh = new P2shScript($p2wsh);

$unsigned = (new TxBuilder())
    ->spendOutPoint($outpoint)
    ->output(95590000, $p2sh->getOutputScript())
    ->get();

$signed = (new Signer($unsigned, Bitcoin::getEcAdapter()))
    ->sign(0, $key, $txOut)
    ->get();

echo $signed->getHex() . PHP_EOL;
