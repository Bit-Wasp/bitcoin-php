<?php

require "vendor/autoload.php";

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface as I;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;

$wif = 'QP3p9tRpTGTefG4a8jKoktSWC7Um8qzvt8wGKMxwWyW3KTNxMxN7';

\BitWasp\Bitcoin\Bitcoin::setNetwork(\BitWasp\Bitcoin\Network\NetworkFactory::bitcoinSegnet());


$key = PrivateKeyFactory::fromWif($wif);
$pub = $key->getPublicKey();
$addr = $pub->getAddress();

$redeemScript = \BitWasp\Bitcoin\Script\ScriptFactory::scriptPubKey()->multisig(1, [$pub]);
echo $redeemScript->getHex() . "\n";

$p2sh = \BitWasp\Bitcoin\Script\ScriptFactory::p2sh()->parse($redeemScript);
$addrSH = $p2sh->getAddress()->getHash();
echo $addrSH. PHP_EOL;

$witness = new WitnessProgram(0, \BitWasp\Bitcoin\Crypto\Hash::sha256($redeemScript->getBuffer()));

$outpoint = new OutPoint(Buffer::hex('9065ff2553b170d0159a6781654a6e43c9aea883cef757b016bff2685ef8504b'), 0);
$value = (new \BitWasp\Bitcoin\Amount())->toSatoshis('0.5');
$scriptPubKey = $p2sh->getOutputScript();

$spend = TransactionFactory::build()
    ->spendOutPoint($outpoint)
    ->output('49000000', $p2sh->getOutputScript())
    ->getAndReset();

$spend = (new \BitWasp\Bitcoin\Transaction\Factory\TxWitnessSigner($spend, \BitWasp\Bitcoin\Bitcoin::getEcAdapter()))
    ->sign(0, '500000000', $key, $scriptPubKey, $redeemScript, \BitWasp\Bitcoin\Transaction\SignatureHash\SigHash::ALL)
    ->get();

$consensus = \BitWasp\Bitcoin\Script\ScriptFactory::consensus(InterpreterInterface::VERIFY_P2SH | InterpreterInterface::VERIFY_WITNESS);
$validator = $spend->validator()->checkSignature($consensus, 0, '50000000', $scriptPubKey);
echo $spend->getWitnessBuffer()->getHex() . "\n";
var_dump($validator);