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

$outpoint = new OutPoint(Buffer::hex('23d6640c3f3383ffc8233fbd830ee49162c720389bbba1c313a43b06a235ae13', 32), 0);

$destination = new WitnessProgram(0, $key->getPubKeyHash());
$p2sh = new \BitWasp\Bitcoin\Script\P2shScript($destination->getScript());

$value = 95590000;
$txOut = new \BitWasp\Bitcoin\Transaction\TransactionOutput($value, $p2sh->getOutputScript());

$tx = TransactionFactory::build()
    ->spendOutPoint($outpoint)
    ->payToAddress(94550000, $key->getPublicKey()->getAddress())
    ->get();

$signed = new \BitWasp\Bitcoin\Transaction\Factory\Signer($tx, $ec);
$signed->sign(0, $key, $txOut, $destination->getScript());
$ss = $signed->get();

$consensus = ScriptFactory::consensus(
    \BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface::VERIFY_P2SH |
    \BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface::VERIFY_WITNESS
);
echo "Script validation result: " . ($ss->validator()->checkSignature($consensus, 0, 95590000, $p2sh->getOutputScript()) ? "yay\n" : "nay\n");

echo PHP_EOL;
echo $ss->getWitnessBuffer()->getHex() . PHP_EOL. PHP_EOL;
echo $ss->getHex() . PHP_EOL;