<?php

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2pkhScriptDataFactory;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\WitnessScript;

require __DIR__ . "/../vendor/autoload.php";

$addrReader = new AddressCreator();
$privFactory = new PrivateKeyFactory();
$priv = $privFactory->fromWif('L1U6RC3rXfsoAx3dxsU1UcBaBSRrLWjEwUGbZPxWX9dBukN345R1');
$publicKey = $priv->getPublicKey();

$helper = new P2pkhScriptDataFactory();
$scriptData = $helper->convertKey($publicKey);
$script = $scriptData->getScriptPubKey();

### Key hash types
echo "key hash types\n";

$p2pkh = $scriptData->getAddress($addrReader);
echo " * p2pkh address: {$p2pkh->getAddress()}\n";

#### Script hash types

echo "\nscript hash types:\n";
// taking an available script to be another addresses redeem script..
$redeemScript = new P2shScript($p2pkh->getScriptPubKey());
$p2shAddr = $redeemScript->getAddress();
echo " * p2sh: {$p2shAddr->getAddress()}\n";


$p2wshScript = new WitnessScript($p2pkh->getScriptPubKey());
$p2wshAddr = $p2wshScript->getAddress();
echo " * p2wsh: {$p2wshAddr->getAddress()}\n";

$p2shP2wshScript = new P2shScript(new WitnessScript($p2pkh->getScriptPubKey()));
$p2shP2wshAddr = $p2shP2wshScript->getAddress();
echo " * p2sh|p2wsh: {$p2shP2wshAddr->getAddress()}\n";
