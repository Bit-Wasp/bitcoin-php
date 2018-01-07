<?php

require_once __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\Interpreter\Checker;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Transaction;

$ecAdapter = Bitcoin::getEcAdapter();
$scriptSig = ScriptFactory::create()->int(1)->int(1)->getScript();
$scriptPubKey = ScriptFactory::create()->opcode(Opcodes::OP_ADD)->int(2)->opcode(Opcodes::OP_EQUAL)->getScript();
echo "Formed script: " . $scriptSig->getHex() . " " . $scriptPubKey->getHex() . "\n";

$flags = 0;
$interpreter = new Interpreter($ecAdapter);
$result = $interpreter->verify($scriptSig, $scriptPubKey, $flags, new Checker($ecAdapter, new Transaction(), 0, 0));
echo "Script result: " . ($result ? 'true' : 'false') . "\n";
