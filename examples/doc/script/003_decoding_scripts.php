<?php

use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;

require __DIR__ . "/../../../vendor/autoload.php";

$scriptPubKey = ScriptFactory::create()->int(1)->opcode(Opcodes::OP_ADD)->int(2)->opcode(Opcodes::OP_EQUAL)->getScript();
$opcodes = $scriptPubKey->getOpcodes();

foreach ($scriptPubKey->getScriptParser()->decode() as $operation) {
    if ($operation->isPush()) {
        echo "push [{$operation->getData()}]\n";
    } else {
        echo "op [{$opcodes->getOp($operation->getOp())}]\n";
    }
}
