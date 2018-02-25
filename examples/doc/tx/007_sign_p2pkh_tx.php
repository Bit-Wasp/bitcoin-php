<?php

require __DIR__ . "/../../../vendor/autoload.php";

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;

/**
 * This example shows spending a P2PKH txout,
 * in a one input, one output transaction. These
 * transactions don't have a change output,
 * make sure you transfer the entire balance
 * of the output (minus the fee)!
 *
 * The private key and txOut are known.
 * We first create an unsigned transaction, before
 * accessing and signing the first input.
 *
 * The steps in Signer will all throw exceptions
 * if an inconsistency in user input is detected.
 */

$privKeyFactory = new PrivateKeyFactory(true);
$privateKey = $privKeyFactory->fromWif('5Hwig3iZrm6uxS6Ch1egmJGyC89Q76X5tgVgtbEcLTPTx3aW5Zi');
$txOut = new TransactionOutput(
    1501000,
    ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPubKeyHash())
);

// Create a spend transaction
$addressCreator = new AddressCreator();
$transaction = TransactionFactory::build()
    ->input('87f7b7639d132e9817f58d3fe3f9f65ff317dc780107a6c10cba5ce2ad1e4ea1', 0)
    ->payToAddress(1500000, $addressCreator->fromString('1DUzqgG31FvNubNL6N1FVdzPbKYWZG2Mb6'))
    ->get();

$signer = new Signer($transaction);
$input = $signer->input(0, $txOut);
$input->sign($privateKey);
$signed = $signer->get();

echo "txid: {$signed->getTxId()->getHex()}\n";
echo "raw: {$signed->getHex()}\n";
echo "input valid? " . ($input->verify() ? "true" : "false") . PHP_EOL;
