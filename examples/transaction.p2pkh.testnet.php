<?php

require_once __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Rpc\RpcFactory;

Bitcoin::setNetwork(\BitWasp\Bitcoin\Network\NetworkFactory::bitcoinTestnet());
$network = Bitcoin::getNetwork();
$host = '127.0.0.1';
$port = '18332';
$user = getenv('BITCOINLIB_RPC_USER') ?: 'bitcoinrpc';
$pass = getenv('BITCOINLIB_RPC_PASSWORD') ?: 'BBpsLqmCCx7Vp8sRd5ygDxFkHZBgWLTTi55QwWgN6Ng6';

$bitcoind = RpcFactory::bitcoind($host, $port, $user, $pass);

$privateKey = PrivateKeyFactory::fromHex('17a2209250b59f07a25b560aa09cb395a183eb260797c0396b82904f918518d5');
echo "[Key: " . $privateKey->toWif($network) . " Address " . $privateKey->getAddress()->getAddress($network) . "]\n";

$myTx = $bitcoind->getrawtransaction('8f792f166bc5f3821515cb5dfff1a967f5acc3d162d1019283186fbf6b119bb5', true);
$spendOutput = 1;
$recipient = AddressFactory::fromString('n1b2a9rFvuU9wBgBaoWngNvvMxRV94ke3x');
echo "[Send to: " . $recipient->getAddress($network) . " \n";

$builder = TransactionFactory::builder()
    ->spendOutput($myTx, $spendOutput)
    ->payToAddress($recipient, 40000);
echo "setup stage\n";
print_r($builder);

echo "signing\n";
$builder->signInputWithKey($privateKey, $myTx->getOutputs()->getOutput($spendOutput)->getScript(), 0);

print_r($builder);
echo "Generate transaction: \n";
$new = $builder
    ->getTransaction();

print_r($new);


echo $new->getHex()."\n";
print_r($new);
try {
    echo $bitcoind->sendrawtransaction($new, true);
} catch (\Exception $e) {
    echo "\n\nException: (".$e->getCode().") ".$e->getMessage()."\n";
}
