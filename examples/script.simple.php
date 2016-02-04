<?php

require_once "../vendor/autoload.php";

use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Transaction;

$ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();

$scriptSig = ScriptFactory::create()->int(1)->int(1)->getScript();
$scriptPubKey = ScriptFactory::create()->op('OP_ADD')->int(2)->op('OP_EQUAL')->getScript();

echo "Formed script: " . $scriptSig->getHex() . " " . $scriptPubKey->getHex() . "\n";

$flags = 0;
$i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter($ec);

$result = $i->verify($scriptSig, $scriptPubKey, $flags, new \BitWasp\Bitcoin\Script\Interpreter\Checker($ec, new Transaction(), 0, 0));
echo "Script result: " . ($result ? 'true' : 'false') . "\n";