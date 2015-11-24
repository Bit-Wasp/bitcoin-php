<?php

require_once "../vendor/autoload.php";

use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Script\Script;

$ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();

$scriptSig = ScriptFactory::create()->int(1)->int(1)->getScript();
$scriptPubKey = ScriptFactory::create()->op('OP_ADD')->int(2)->op('OP_EQUAL')->getScript();

echo "Formed script: " . $scriptSig->getHex() . " " . $scriptPubKey->getHex() . "\n";

$flags = new \BitWasp\Bitcoin\Flags(0);
$i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter($ec, new Transaction, $flags);

$result = $i->verify($scriptSig, $scriptPubKey, 0);
echo "Script result: " . ($result ? 'true' : 'false') . "\n";