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

$destScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPublicKey());
$program = new WitnessProgram(0, Hash::sha256($destScript->getBuffer()));

$outpoint = new OutPoint(Buffer::hex('c2197f15d510304f1463230c0e61566bfb8dcadb7e1c510d3c0470bcfbca2194', 32), 0);
$txOut = new \BitWasp\Bitcoin\Transaction\TransactionOutput(
    99990000,
    $program->getScript()
);

$tx = TransactionFactory::build()
    ->spendOutPoint($outpoint)
    ->payToAddress(97900000, $key->getPublicKey()->getAddress())
    ->get();

$signed = new \BitWasp\Bitcoin\Transaction\Factory\Signer($tx, $ec);
$signed->sign(0, $key, $txOut, null, $destScript);

$ss = $signed->get();

$consensus = ScriptFactory::consensus(
    \BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface::VERIFY_P2SH |
    \BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface::VERIFY_WITNESS
);
echo "Script validation result: " . ($ss->validator()->checkSignature($consensus, 0, 99990000, $txOut->getScript()) ? "yay\n" : "nay\n");

echo PHP_EOL;
echo $ss->getWitnessBuffer()->getHex() . PHP_EOL. PHP_EOL;
//echo $ss->getHex() . PHP_EOL;