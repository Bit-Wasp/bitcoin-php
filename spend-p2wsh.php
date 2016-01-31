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

echo "Bitcoin address: " . $key->getPublicKey()->getAddress()->getAddress() . PHP_EOL;

$outpoint = new OutPoint(Buffer::hex('f6363e0c13f0b85859a1da1c2bfbc96fd91b71761b749edb9f36e4e167982247', 32), 0);
$destScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPublicKey());
echo $destScript->getScriptParser()->getHumanReadable() . PHP_EOL;
$program = new WitnessProgram(0, Hash::sha256($destScript->getBuffer()));
$scriptPubKey = $program->getOutputScript();

$tx = TransactionFactory::build()
    ->spendOutPoint($outpoint)
    ->payToAddress(98900000, $key->getPublicKey()->getAddress())
    ->get();

$signed = new \BitWasp\Bitcoin\Transaction\Factory\TxWitnessSigner($tx, $ec);
$signed->sign(0, 99900000, $key, $scriptPubKey, null, $destScript);
print_R($signed);
$ss = $signed->get();

$consensus = ScriptFactory::consensus(
    \BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface::VERIFY_P2SH |
    \BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface::VERIFY_WITNESS
);
echo "Script validation result: " . ($ss->validator()->checkSignature($consensus, 0, 99900000, $scriptPubKey) ? "yay\n" : "nay\n");

echo PHP_EOL;
echo $ss->getWitnessBuffer()->getHex() . PHP_EOL. PHP_EOL;
//echo $ss->getHex() . PHP_EOL;