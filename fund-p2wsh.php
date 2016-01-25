<?php

require "vendor/autoload.php";

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Bitcoin;

$wif = 'QP3p9tRpTGTefG4a8jKoktSWC7Um8qzvt8wGKMxwWyW3KTNxMxN7';

$s = \BitWasp\Bitcoin\Network\NetworkFactory::bitcoinSegnet();
Bitcoin::setNetwork($s);
$ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();
$key = PrivateKeyFactory::fromWif($wif);

echo $key->getPublicKey()->getAddress()->getAddress() . PHP_EOL;


$outpoint = new OutPoint(Buffer::hex('77d11867bcaaadee13f6f322874d3498d910047d5666dcc02402ff07fc0bec3b', 32), 0);
$scriptPubKey = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPublicKey());

$redeemScript = $scriptPubKey;
$destination = new WitnessProgram(0, Hash::sha256($redeemScript->getBuffer()));

$tx = TransactionFactory::build()
    ->spendOutPoint($outpoint)
    ->output(99900000, $destination->getOutputScript())
    ->get();

$signed = new \BitWasp\Bitcoin\Transaction\Factory\TxWitnessSigner($tx, $ec);
$signed->sign(0, 100000000, $key, $destination->getOutputScript(), null, $scriptPubKey);
$ss = $signed->get();

echo $ss->getHex() . PHP_EOL;