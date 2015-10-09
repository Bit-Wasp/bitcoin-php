<?php

require_once "../vendor/autoload.php";

use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Transaction;

$ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();

$script = ScriptFactory::create()->op('OP_1')->op('OP_1')->op('OP_ADD')->op('OP_2')->op('OP_EQUALVERIFY')->getScript();

echo "Formed script: " . $script->getHex() . "\n";
print_r($script->getScriptParser()->parse());

$flags = new \BitWasp\Bitcoin\Flags(0);
$i = new \BitWasp\Bitcoin\Script\Interpreter\Interpreter($ec, new Transaction(), $flags);
$result = $i->setScript($script)->run();
echo "Script result: " . ($result ? 'true' : 'false') . "\n";