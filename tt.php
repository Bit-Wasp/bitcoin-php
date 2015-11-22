<?php

require "vendor/autoload.php";

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use Mdanter\Ecc\EccFactory;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\ConsensusFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\Factory\TxSigner;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;

$ec = EcAdapterFactory::getAdapter(new Math(), EccFactory::getSecgCurves()->generator256k1());
$privateKey = PrivateKeyFactory::fromHex('4141414141414141414141414141414141414141414141414141414141414141', false, $ec);

$consensus = new ConsensusFactory($ec);
$flags = $consensus->defaultFlags();


$ms = ScriptFactory::p2sh()->multisig(1, [$privateKey->getPublicKey()]);;
/** @var ScriptInterface $rs */
/** @var ScriptInterface $os */
list ($rs, $os) = $ms;
$vectors[] = [
true,
$ec,
$flags,
$privateKey,
$os,
$rs,
];



// Create a fake tx to spend - an output script we supposedly can spend.
$builder = new TxBuilder();
$fake = $builder->output(1, $os)->getAndReset();

// Here is where
$spend = $builder->spendOutputFrom($fake, 0)->get();

$signer = new TxSigner($ec, $spend);
$signer->sign(0, $privateKey, $os, $rs);

$spendTx = $signer->get();
$scriptSig = $spendTx->getInput(0)->getScript();

echo $rs->getHex() . PHP_EOL;
echo $os->getHex() . PHP_EOL;
echo $scriptSig->getHex() . "\n";

$i = new Interpreter($ec, $spendTx, $flags);
echo ($i->verify($scriptSig, $os, 0) ? 'yes' : 'no') . "\n";