<?php

require "../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionBuilder;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

$ecAdapter = Bitcoin::getEcAdapter();

// Two users independently create private keys.
$pk1 = PrivateKeyFactory::fromHex('421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87');
$pk2 = PrivateKeyFactory::fromHex('f7225388c1d69d57e6251c9fda50cbbf9e05131e5adb81e5aa0422402f048162');

// They exchange public keys, and a multisignature address is made.
$redeemScript = ScriptFactory::multisig(2, [$pk1->getPublicKey(), $pk2->getPublicKey()]);
$outputScript = $redeemScript->getOutputScript();

// The address is funded with a transaction (fake, for the purposes of this script).
// You would do getrawtransaction normally
$spendTx = new Transaction();
$spendTx->getInputs()->addInput(new TransactionInput(
    '4141414141414141414141414141414141414141414141414141414141414141',
    0
));
$spendTx->getOutputs()->addOutput(new TransactionOutput(
    50,
    $outputScript
));

// One party wants to spend funds. He creates a transaction spending the funding tx to his address.
$builder = new TransactionBuilder($ecAdapter);
$builder->spendOutput($spendTx, 0)
    ->payToAddress($pk1->getAddress(), 50)
    ->signInputWithKey($pk1, $outputScript, 0, $redeemScript)
    ->signInputWithKey($pk2, $outputScript, 0, $redeemScript);

$rawTx = $builder->getTransaction()->getHex();

echo "Fully signed transaction: " . $builder->getTransaction()->getHex() . "\n";

