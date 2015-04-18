<?php

require_once __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Rpc\RpcFactory;

$network = Bitcoin::getNetwork();
$ecAdapter = Bitcoin::getEcAdapter();
$host = '127.0.0.1';
$port = '18332';
$user = getenv('BITCOINLIB_RPC_USER') ?: 'bitcoinrpc';
$pass = getenv('BITCOINLIB_RPC_PASSWORD') ?: 'BBpsLqmCCx7Vp8sRd5ygDxFkHZBgWLTTi55QwWgN6Ng6';
Bitcoin::setNetwork(\BitWasp\Bitcoin\Network\NetworkFactory::bitcoinTestnet());
$bitcoind = RpcFactory::bitcoind($host, $port, $user, $pass);

// Address to fund this test
$fundsKey = PrivateKeyFactory::fromWif('cQTqzY1hhC8u4aeFmqodENTnJvxgSk316PakYVgcFaHqAa4aCpwW');
$address = $fundsKey->getAddress()->getAddress();

// Txid / spendable output of funding transaction, funds will be moved from here -> multisig
$myTx = $bitcoind->getrawtransaction('e76ef5659124d5cacb0fa2536f8af8e279aea016be1408caa492295a6f85a214', true);
$spendOutput = 0;

// Funds will be send from Multisig -> this
$recipient = \BitWasp\Bitcoin\Address\AddressFactory::fromString('n1b2a9rFvuU9wBgBaoWngNvvMxRV94ke3x');


// Begin

$privateKey1 = PrivateKeyFactory::fromHex('17a2209250b59f07a25b560aa09cb395a183eb260797c0396b82904f918518d5', true);
$privateKey2 = PrivateKeyFactory::fromHex('17a2209250b59f07a25b560aa09cb395a183eb260797c0396b82904f918518d6', true);
$redeemScript = ScriptFactory::multisig(2, array($privateKey1->getPublicKey(), $privateKey2->getPublicKey()));

// First, move money from fundsKey to the multisig address
$new = new \BitWasp\Bitcoin\Transaction\TransactionBuilder($ecAdapter);
$new->spendOutput($myTx, $spendOutput)
    ->payToAddress($redeemScript->getAddress(), 200000);

echo "[Fund this address: $address]\n";
echo "[P2SH address: " . $redeemScript->getAddress() ." ]\n";

$new->signInputWithKey($fundsKey, $myTx->getOutputs()->getOutput($spendOutput)->getScript(), 0);
$tx = $new->getTransaction();

try {
    echo "try sending to multisig address\n";
    echo $tx->getHex()."\n";
    $txid = $bitcoind->sendrawtransaction($tx, true);
    echo "[Sent with $txid] \n";
} catch (\BitWasp\Bitcoin\Exceptions\JsonRpcError $e) {
    echo "FAILURE\n";
    echo $e->getMessage()."\n" . $e->getCode()."\n";
    echo $e->getTraceAsString()."\n";
}

echo "Now redeem from the multisig address, send to " . $recipient->getAddress() . "\n";
$new = new \BitWasp\Bitcoin\Transaction\TransactionBuilder($ecAdapter);
$new
    ->spendOutput($tx, 0)
    ->payToAddress($recipient, 50000);

$tx = $new->signInputWithKey($privateKey1, $redeemScript->getOutputScript(), 0, $redeemScript)
    ->signInputWithKey($privateKey2, $redeemScript->getOutputScript(), 0, $redeemScript)
    ->getTransaction();

echo $tx->getHex()."\n";
try {
    $txid = $bitcoind->sendrawtransaction($tx, true);
    echo "done!\n";
} catch (\Exception $e) {
    echo "exception triggered\n";
    echo $e->getMessage()."\n" . $e->getCode()."\n";
    echo $e->getTraceAsString()."\n";
}
