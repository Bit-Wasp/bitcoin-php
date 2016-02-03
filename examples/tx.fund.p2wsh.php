<?php

require "../vendor/autoload.php";

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

$outpoint = new OutPoint(Buffer::hex('499c2bff1499bf84bc63058fda3ed112c2c663389f108353798a1bd6a6651afe', 32), 0);
$scriptPubKey = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPublicKey());
$value = 100000000;
$txOut = new \BitWasp\Bitcoin\Transaction\TransactionOutput($value, $scriptPubKey);

$destination = new WitnessProgram(0, Hash::sha256($scriptPubKey->getBuffer()));

$tx = TransactionFactory::build()
    ->spendOutPoint($outpoint)
    ->output(99990000, $destination->getScript())
    ->get();

$signed = new \BitWasp\Bitcoin\Transaction\Factory\Signer($tx, $ec);
$signed->sign(0, $key, $txOut);
$ss = $signed->get();

echo $ss->getBuffer()->getHex() . PHP_EOL;