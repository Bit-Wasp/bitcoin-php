<?php

require __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\SignData;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

// Same private keys as tx.fund.p2sh.multisig.php
$privKeyFactory = new PrivateKeyFactory(true);
$privKey1 = $privKeyFactory->fromWif("L3WyxitKt4DQrhcdTEnyzLWWyurf2fz1iqCdAbuUXaUmSM328JWv");
$privKey2 = $privKeyFactory->fromWif("L45C3XqWziQVnifEQdzwYmpGG5SPXxFv5Es8bnjE5QXZF5K8bSGh");

// make a 2-of-2 multisignature script
$multisig = ScriptFactory::scriptPubKey()->multisig(2, array_map(function (PrivateKeyInterface $priv) {
    return $priv->getPublicKey();
}, [$privKey1, $privKey2]));

$p2shMultisig = new P2shScript($multisig);
$scriptPubKey = $p2shMultisig->getOutputScript();

// TXID is the txid from tx.fund.p2sh.multisig.php
$outpoint = new OutPoint(Buffer::hex('316ab651d62bbe6aff0c2506b3ac5a7dcdf052352dc1013178c52f92579b6911'), 0);
$txOut = new TransactionOutput(95590000, $scriptPubKey);

$unsigned = (new TxBuilder())
    ->spendOutPoint($outpoint)
    ->output(95580000, $scriptPubKey)
    ->get();

$signer = new Signer($unsigned);

$signData = (new SignData())->p2sh($p2shMultisig);
$input = $signer->input(0, $txOut, $signData);

// several calls to sign are necessary this time
$input->sign($privKey1);
$input->sign($privKey2);

// Check signatures
echo "Script validation result: " . ($input->verify() ? "yay\n" : "nay\n");

$signed = $signer->get();

echo $signed->getHex() . PHP_EOL;
