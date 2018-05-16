<?php

require __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

// Lets pretend the coins are owned by this guy
$privKeyFactory = new PrivateKeyFactory(true);
$originPriv = $privKeyFactory->fromWif("KzBmWku6EuUXbhSym74RXUE7bKWdNanc8vTqxFrMxEstofCWsKgH");
$originSpk = ScriptFactory::scriptPubKey()->p2pkh($originPriv->getPubKeyHash());

// 2 people want to receive BTC in a 2-of-2, so they contribute their
// public keys, and make a P2SH multisignature address
$privKey1 = $privKeyFactory->fromWif("L3WyxitKt4DQrhcdTEnyzLWWyurf2fz1iqCdAbuUXaUmSM328JWv");
$privKey2 = $privKeyFactory->fromWif("L45C3XqWziQVnifEQdzwYmpGG5SPXxFv5Es8bnjE5QXZF5K8bSGh");
$pubKeys = array_map(function (PrivateKeyInterface $priv) {
    return $priv->getPublicKey();
}, [$privKey1, $privKey2]);

// make a 2-of-2 multisignature script
$multisig = ScriptFactory::scriptPubKey()->multisig(2, $pubKeys);

// use the P2shScript 'decorator' to 'extend' script with extra
// functions relevant to a P2SH script
$p2shMultisig = new P2shScript($multisig);

// such as getOutputScript!
$scriptPubKey = $p2shMultisig->getOutputScript();

// some made up txid/outpoint for the test, but owned by originPriv
$outpoint = new OutPoint(Buffer::hex('a54255bc701c9746319b97d044bf90d4193d5f513de0fe759a1dff4e0c760155', 32), 0);
$txOut = new TransactionOutput(100000000, $originSpk);

$unsigned = (new TxBuilder())
    ->spendOutPoint($outpoint)
    ->output(95590000, $scriptPubKey)
    ->get();

$signer = new Signer($unsigned);
$input = $signer->input(0, $txOut);
$input->sign($originPriv);

// Check signatures
echo "Script validation result: " . ($input->verify() ? "yay\n" : "nay\n");

$signed = $signer->get();

echo $signed->getHex() . PHP_EOL;
echo "txid: {$signed->getTxId()->getHex()}\n";
