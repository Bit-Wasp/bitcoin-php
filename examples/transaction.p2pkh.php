<?php

require_once "../vendor/autoload.php";

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Transaction\TransactionFactory;
use Afk11\Bitcoin\Key\PrivateKeyFactory;
use Afk11\Bitcoin\Key\PublicKeyFactory;
use Afk11\Bitcoin\Rpc\RpcFactory;

$network = Bitcoin::getNetwork();
$host = '127.0.0.1';
$port = '8332';
$user = 'bitcoinrpc';
$pass = 'BBpsLqmCCx7Vp8sRd5ygDxFkHZBgWLTTi55QwWgN6Ng6';

$bitcoind = RpcFactory::bitcoind($host, $port, $user, $pass);

$privateKey = PrivateKeyFactory::fromHex('17a2209250b59f07a25b560aa09cb395a183eb260797c0396b82904f918518d5');
echo "[Key: " . $privateKey->toWif($network) . " Address " . $privateKey->getAddress()->getAddress($network) . "]\n";

$myTx = $bitcoind->getrawtransaction('b8abff4fa6a36cbfbd61d0351be3433a95c34538f5174446420610288f1e8958', true);

$recipient = PublicKeyFactory::fromHex('02a2f9c63fea13472d6c0c6502ec08e6a9ccc2b44b93ad2308c683d1e827505093')->getAddress($network);
echo "[Send to: " . $recipient->getAddress($network) . " \n";

$new = TransactionFactory::builder()
    ->spendOutput($myTx, 0)
    ->payToAddress($recipient, 20000)
    ->signWithKey($privateKey)
    ->getTransaction();

print_r($new->toArray());
echo $new->getBuffer()."\n";
echo $bitcoind->sendrawtransaction($new);
