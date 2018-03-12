<?php

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Address\SegwitAddress;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;

require __DIR__ . "/../vendor/autoload.php";

$privFactory = PrivateKeyFactory::compressed();
$priv = $privFactory->fromWif('L1U6RC3rXfsoAx3dxsU1UcBaBSRrLWjEwUGbZPxWX9dBukN345R1');
$publicKey = $priv->getPublicKey();
$pubKeyHash = $publicKey->getPubKeyHash();

### Key hash types
echo "key hash types\n";
$p2pkh = new PayToPubKeyHashAddress($pubKeyHash);
echo " * p2pkh address: {$p2pkh->getAddress()}\n";

$p2wpkhWP = WitnessProgram::v0($publicKey->getPubKeyHash());
$p2wpkh = new SegwitAddress($p2wpkhWP);
$address = $p2wpkh->getAddress();
echo " * v0 key hash address: {$address}\n";

#### Script hash types

echo "\nscript hash types:\n";
// taking an available script to be another addresses redeem script..
$redeemScript = $p2pkh->getScriptPubKey();

$p2sh = new ScriptHashAddress($redeemScript->getScriptHash());
echo " * p2sh: {$p2sh->getAddress()}\n";

$p2wshWP = WitnessProgram::v0($redeemScript->getWitnessScriptHash());
$p2wsh = new SegwitAddress($p2wshWP);
echo " * p2wsh: {$p2wsh->getAddress()}\n";

$p2shP2wsh = new ScriptHashAddress($p2wshWP->getScript()->getScriptHash());
echo " * p2sh|p2wsh: {$p2shP2wsh->getAddress()}\n";
