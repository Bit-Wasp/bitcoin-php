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


$outpoint = new OutPoint(Buffer::hex('87f7b7639d132e9817f58d3fe3f9f65ff317dc780107a6c10cba5ce2ad1e4ea1', 32), 0);

$program = new WitnessProgram(0, $key->getPubKeyHash());
$scriptPubKey = $program->getOutputScript();

$tx = TransactionFactory::build()
    ->spendOutPoint($outpoint)
    ->payToAddress(98900000, $key->getPublicKey()->getAddress())
    ->get();

$signed = new \BitWasp\Bitcoin\Transaction\Factory\TxWitnessSigner($tx, $ec);
$signed->sign(0, 99900000, $key, $scriptPubKey);
$ss = $signed->get();

$consensus = ScriptFactory::consensus(
    \BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface::VERIFY_P2SH |
    \BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface::VERIFY_WITNESS
);
echo "Script validation result: " . ($ss->validator()->checkSignature($consensus, 0, 99900000, $scriptPubKey) ? "yay\n" : "nay\n");

echo PHP_EOL;
echo $ss->getWitnessBuffer()->getHex() . PHP_EOL. PHP_EOL;
echo $ss->getHex() . PHP_EOL;