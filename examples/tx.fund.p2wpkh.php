<?php

require "../vendor/autoload.php";

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Bitcoin;

$wif = 'QP3p9tRpTGTefG4a8jKoktSWC7Um8qzvt8wGKMxwWyW3KTNxMxN7';

$s = \BitWasp\Bitcoin\Network\NetworkFactory::bitcoinSegnet();
Bitcoin::setNetwork($s);
$ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();
$key = PrivateKeyFactory::fromWif($wif);

echo $key->getPublicKey()->getAddress()->getAddress() . PHP_EOL;


$outpoint = new OutPoint(Buffer::hex('874381bb431eaaae16e94f8b88e4ea7baf2ebf541b2ae11ec10d54c8e03a237f', 32), 0);
$scriptPubKey = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPublicKey());
$value = 100000000;
$txOut = new \BitWasp\Bitcoin\Transaction\TransactionOutput($value, $scriptPubKey);

$destination = new WitnessProgram(0, $key->getPubKeyHash());

$tx = TransactionFactory::build()
    ->spendOutPoint($outpoint)
    ->output(99900000, $destination->getScript())
    ->get();

$signed = new \BitWasp\Bitcoin\Transaction\Factory\Signer($tx, $ec);
$signed->sign(0, $key, $txOut);
$ss = $signed->get();

echo $ss->getHex() . PHP_EOL;