<?php

require "../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\TxSigner;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;

$ecAdapter = Bitcoin::getEcAdapter();

// Two users independently create private keys.
$pk1 = PrivateKeyFactory::fromHex('421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87');
$pk2 = PrivateKeyFactory::fromHex('f7225388c1d69d57e6251c9fda50cbbf9e05131e5adb81e5aa0422402f048162');


// They exchange public keys, and a multisignature address is made.
$redeemScript = ScriptFactory::scriptPubKey()->multisig(2, [$pk1->getPublicKey(), $pk2->getPublicKey()]);
$outputScript = ScriptFactory::scriptPubKey()->payToScriptHash($redeemScript);


// The address is funded with a transaction (fake, for the purposes of this script).
// You would do getrawtransaction normall
$fundTx = (new TxBuilder())
    ->input('4141414141414141414141414141414141414141414141414141414141414141', 0)
    ->output(50, $outputScript)
    ->get();


// One party wants to spend funds. He creates a transaction spending the funding tx to his address.
$spendTx = (new TxBuilder())
    ->spendOutputFrom($fundTx, 0)
    ->payToAddress(50, $pk1->getAddress())
    ->get();


// Two parties sign the transaction (can be done in steps)
$signer = new TxSigner($ecAdapter, $spendTx);
$signer
    ->sign(0, $pk1, $outputScript, $redeemScript)
    ->sign(0, $pk2, $outputScript, $redeemScript);

$rawTx = $signer->get()->getHex();

echo "Fully signed transaction: " . $signer->get()->getHex() . "\n";

