<?php

require_once "../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Rpc\RpcFactory;

$network = Bitcoin::getNetwork();
$host = '127.0.0.1';
$port = '8332';
$user = 'bitcoinrpc';
$pass = 'BBpsLqmCCx7Vp8sRd5ygDxFkHZBgWLTTi55QwWgN6Ng6';

$network = Bitcoin::getNetwork();
$bitcoind = RpcFactory::bitcoind($host, $port, $user, $pass);

$privateKey1 = PrivateKeyFactory::fromHex('17a2209250b59f07a25b560aa09cb395a183eb260797c0396b82904f918518d5', true);
$privateKey2 = PrivateKeyFactory::fromHex('17a2209250b59f07a25b560aa09cb395a183eb260797c0396b82904f918518d6', true);

$redeemScript = ScriptFactory::multisig(2, array($privateKey1->getPublicKey(), $privateKey2->getPublicKey()));
$recipient = $redeemScript->getAddress();
echo "[P2SH address: " . $recipient->getAddress($network) ." ]\n";

$myTx = $bitcoind->getrawtransaction('6783f952acbce7cbe09cb1ad495462485043ac73be318cade5e51e8639efae3a', true);

$recipient = PublicKeyFactory::fromHex('02a2f9c63fea13472d6c0c6502ec08e6a9ccc2b44b93ad2308c683d1e827505093')->getAddress($network);

$new = TransactionFactory::builder()
    ->spendOutput($myTx, 0)
    ->payToAddress($recipient, 20000)
    ->signInputWithKey($privateKey1, 0, $redeemScript)
    ->getTransaction();

// OK P2SH isn't complete yet.

print_r($new->toArray());

echo $new->getBuffer()."\n";
//echo $bitcoind->sendrawtransaction($new);
