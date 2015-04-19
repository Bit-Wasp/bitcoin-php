<?php

require_once __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Rpc\RpcFactory;

$network = Bitcoin::getNetwork();
$host = '127.0.0.1';
$port = '18332';
$user = getenv('BITCOINLIB_RPC_USER') ?: 'bitcoinrpc';
$pass = getenv('BITCOINLIB_RPC_PASSWORD') ?: 'BBpsLqmCCx7Vp8sRd5ygDxFkHZBgWLTTi55QwWgN6Ng6';

Bitcoin::setNetwork(\BitWasp\Bitcoin\Network\NetworkFactory::bitcoinTestnet());

$network = Bitcoin::getNetwork();
$ecAdapter = Bitcoin::getEcAdapter();

$bitcoind = RpcFactory::bitcoind($host, $port, $user, $pass);

$privateKey1 = PrivateKeyFactory::fromHex('17a2209250b59f07a25b560aa09cb395a183eb260797c0396b82904f918518d5', true);
$privateKey2 = PrivateKeyFactory::fromHex('17a2209250b59f07a25b560aa09cb395a183eb260797c0396b82904f918518d6', true);
$redeemScript = ScriptFactory::multisig(2, array($privateKey1->getPublicKey(), $privateKey2->getPublicKey()));
$multisig = $redeemScript->getAddress();

echo "[P2SH address: " . $multisig->getAddress($network) ." ]\n";
echo "[private key order: " . implode(", ", array_map(function(PublicKeyInterface $publicKey) use($privateKey1, $privateKey2) {
        if ($publicKey->getBinary() == $privateKey1->getPublicKey()->getBinary()) {
            return "1";
        } else {
            return "2";
        }
}, $redeemScript->getKeys())) . "]\n";

$myTx = $bitcoind->getrawtransaction('6d4f5d2cce43660c29e03a794497da3f204312358ca6a6e47035ef916ce19db9', true);
$spendOutput = 0;
$recipient = AddressFactory::fromString('n1b2a9rFvuU9wBgBaoWngNvvMxRV94ke3x');
echo "[Send to: " . $recipient->getAddress($network) . " \n";

// Prep work - importing from a tx will only bring container to contents of $new - no metadata
$new = new \BitWasp\Bitcoin\Transaction\TransactionBuilder($ecAdapter);
$new
    ->spendOutput($myTx, $spendOutput)
    ->payToAddress($recipient, 100000);

// Start doing things which require state tracking
$new
    ->signInputWithKey($privateKey1, $redeemScript->getOutputScript(), $spendOutput, $redeemScript)
    ->signInputWithKey($privateKey2, $redeemScript->getOutputScript(), $spendOutput, $redeemScript);

$tx = $new->getTransaction();

// Send transaction to multisig address
try {
    $txid = $bitcoind->sendrawtransaction($tx, true);
} catch (\Exception $e) {
    echo "\n\nException: (".$e->getCode().") ".$e->getMessage()."\n";

}
