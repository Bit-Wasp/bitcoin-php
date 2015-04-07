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

$myTx = $bitcoind->getrawtransaction('503b7712e6b67600e6e189123bca21111a1f796e217460845cb7060dbd2882c4', true);
$spendOutput = 0;
$recipient = AddressFactory::fromString('n1b2a9rFvuU9wBgBaoWngNvvMxRV94ke3x');
echo "[Send to: " . $recipient->getAddress($network) . " \n";

// Prep work - importing from a tx will only bring container to contents of $new - no metadata
$new = TransactionFactory::builder()
    ->spendOutput($myTx, $spendOutput)
    ->payToAddress($recipient, 100000);

// Start doing things which require state tracking
$tx = $new
    ->signInputWithKey($privateKey1, $redeemScript->getOutputScript(), $spendOutput, $redeemScript)
    ->signInputWithKey($privateKey2, $redeemScript->getOutputScript(), $spendOutput, $redeemScript)
    ->getTransaction();


// old, before the order of the signatures was fixed:
// assert($tx->getHex() == "0100000001c48228bd0d06b75c846074216e791f1a1121ca3b1289e1e60076b6e612773b5000000000db00483045022100c01c6ae953bf41240a888aeac77df1ff8905585f6c577752fc79475d02e7b62b022045c2dd5ac42b45367de6652420cf0ed193c6e4be082f3c775bb81add4fe4db6c01483045022100ab4744e7b9eb1f1c31d74f06972e67b695a045940dbb4a6eb550600f23eef652022019a75b7a779df525a166112e34aa3ae6261046cf881edf5424fe23d69c5e8ebc0147522102a10a11a6035fc36fe79e932dfbe2767e3f3ab2214261f6401cc0b6672c0bd00721031a2756dd506e45a1142c7f7f03ae9d3d9954f8543f4c3ca56f025df66f1afcba52aeffffffff01a0860100000000001976a914dc27c011389ecb4bf0ab50565a15c48354cb105188ac00000000");

// new, not sure if it's correct, but signature order is fixed
assert($tx->getHex() == "0100000001c48228bd0d06b75c846074216e791f1a1121ca3b1289e1e60076b6e612773b5000000000db00483045022100ab4744e7b9eb1f1c31d74f06972e67b695a045940dbb4a6eb550600f23eef652022019a75b7a779df525a166112e34aa3ae6261046cf881edf5424fe23d69c5e8ebc01483045022100c01c6ae953bf41240a888aeac77df1ff8905585f6c577752fc79475d02e7b62b022045c2dd5ac42b45367de6652420cf0ed193c6e4be082f3c775bb81add4fe4db6c0147522102a10a11a6035fc36fe79e932dfbe2767e3f3ab2214261f6401cc0b6672c0bd00721031a2756dd506e45a1142c7f7f03ae9d3d9954f8543f4c3ca56f025df66f1afcba52aeffffffff01a0860100000000001976a914dc27c011389ecb4bf0ab50565a15c48354cb105188ac00000000");

die("done");

// Send transaction to multisig address
try {
    $txid = $bitcoind->sendrawtransaction($tx, true);
} catch (\Exception $e) {
    echo "\n\nException: (".$e->getCode().") ".$e->getMessage()."\n";
    die();
}
