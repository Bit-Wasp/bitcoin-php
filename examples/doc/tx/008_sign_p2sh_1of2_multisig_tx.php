<?php

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\SignData;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;

require __DIR__ . "/../../../vendor/autoload.php";

/**
 * This example shows a user in a 1-of-2 multisig
 * transaction spending an output, sending some
 * to another address, and the rest to our own
 * address again (the change)
 *
 * The multisignature script is created, from which
 * we can recreate the txout's script.
 *
 * The transaction is prepared using transaction builder
 * before the input is accessed and signed. We need
 * to create a SignData instance and assign the redeemScript
 * because unsigned transactions don't have the redeemScript
 * inside them yet.
 */

$privKeyFactory = new PrivateKeyFactory(true);
$pubKeyFactory = new PublicKeyFactory();
$privateKey1 = $privKeyFactory->fromWif('5Hwig3iZrm6uxS6Ch1egmJGyC89Q76X5tgVgtbEcLTPTx3aW5Zi');

// Our public key
$publicKey1 = $privateKey1->getPublicKey();
// Other users public key
$publicKey2 = $pubKeyFactory->fromHex("02108445281ade9d8f6b7d8bb1825ca40bedb67bca8cdc3aa6603b9b6f831b9e0c");

// The redeemScript needs to be known when spending
$redeemScript = new P2shScript(
    ScriptFactory::scriptPubKey()->multisig(1, [$publicKey1, $publicKey2])
);

$spendFromAddress = $redeemScript->getAddress();
$addressCreator = new AddressCreator();
$sendToAddress = $addressCreator->fromString('1DUzqgG31FvNubNL6N1FVdzPbKYWZG2Mb6');
echo "Spend from {$spendFromAddress->getAddress()}\n";
echo "Send to {$sendToAddress->getAddress()}\n";

$addressCreator = new AddressCreator();
$transaction = TransactionFactory::build()
    ->input('87f7b7639d132e9817f58d3fe3f9f65ff317dc780107a6c10cba5ce2ad1e4ea1', 0)
    ->payToAddress(1500000, $sendToAddress)
    ->payToAddress(12345123, $spendFromAddress) // don't forget your change output!
    ->get();

$txOut = new TransactionOutput(1501000, $redeemScript->getOutputScript());
$signData = (new SignData())
    ->p2sh($redeemScript)
;

$signer = new Signer($transaction);
$input = $signer->input(0, $txOut, $signData);
$input->sign($privateKey1);

$signed = $signer->get();

echo "txid: {$signed->getTxId()->getHex()}\n";
echo "raw: {$signed->getHex()}\n";
echo "input valid? " . ($input->verify() ? "true" : "false") . PHP_EOL;
