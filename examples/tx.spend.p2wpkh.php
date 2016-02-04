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

echo "Bitcoin address: " . $key->getPublicKey()->getAddress()->getAddress() . PHP_EOL;

$outpoint = new OutPoint(Buffer::hex('3382460d9b226a620c22a5ec435d4e0d2e698ee82c184f0331ea438db6ad3bad', 32), 0);

$program = new WitnessProgram(0, $key->getPubKeyHash());
$scriptPubKey = $program->getScript();

$value = 99900000;
$txOut = new \BitWasp\Bitcoin\Transaction\TransactionOutput($value, $scriptPubKey);

$tx = TransactionFactory::build()
    ->spendOutPoint($outpoint)
    ->payToAddress(99850000, $key->getPublicKey()->getAddress())
    ->get();

$signed = new \BitWasp\Bitcoin\Transaction\Factory\Signer($tx, $ec);
$signed->sign(0, $key, $txOut);
$ss = $signed->get();

$consensus = ScriptFactory::consensus(
    \BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface::VERIFY_P2SH |
    \BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface::VERIFY_WITNESS
);
echo "Script validation result: " . ($ss->validator()->checkSignature($consensus, 0, 99900000, $scriptPubKey) ? "yay\n" : "nay\n");

echo PHP_EOL;
echo $ss->getWitnessBuffer()->getHex() . PHP_EOL. PHP_EOL;
echo $ss->getHex() . PHP_EOL;