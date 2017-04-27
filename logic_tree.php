<?php

require "vendor/autoload.php";

use BitWasp\Bitcoin\Script\Path\LogicInterpreter;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Interpreter\Number;
//use BitWasp\Bitcoin\MAST\MASTTree;
use BitWasp\Bitcoin\Crypto\Random\Random;

$interpreter = new LogicInterpreter();

$random = new Random();

$alicePriv = \BitWasp\Bitcoin\Key\PrivateKeyFactory::create(true);
$alicePub = $alicePriv->getPublicKey();

$bobPriv = \BitWasp\Bitcoin\Key\PrivateKeyFactory::create(true);
$bobPub = $bobPriv->getPublicKey();

$rhash1 = $random->bytes(20);
$rhash2 = $random->bytes(20);

//$script = ScriptFactory::sequence([
//    Opcodes::OP_IF,
//    $alicePub->getBuffer(),
//    Opcodes::OP_ELSE,
//    $bobPub->getBuffer(),
//    Opcodes::OP_ENDIF,
//    Opcodes::OP_CHECKSIG
//]);
//
//$branches = $interpreter->astBranches($script);
//foreach ($branches as $script) {
//    echo "  * " .$script->getScriptParser()->getHumanReadable().PHP_EOL;
//}
//$tree = new MASTTree($script);
//print_r($tree);
echo "-----------------------\n";
$script = ScriptFactory::sequence([
    Opcodes::OP_HASH160, Opcodes::OP_DUP, $rhash1, Opcodes::OP_EQUAL,
    Opcodes::OP_IF,
    Number::int(6000)->getBuffer(), Opcodes::OP_CHECKSEQUENCEVERIFY, Opcodes::OP_2DROP, $alicePub->getBuffer(),
    Opcodes::OP_ELSE,
    $rhash2, Opcodes::OP_EQUAL,
    Opcodes::OP_NOTIF,
    Number::int(6000)->getBuffer(), Opcodes::OP_CHECKLOCKTIMEVERIFY, Opcodes::OP_DROP,
    Opcodes::OP_ENDIF,
    $bobPub->getBuffer(),
    Opcodes::OP_ENDIF,
    Opcodes::OP_CHECKSIG
]);


$branches = $interpreter->astBranches($script);
foreach ($branches as $script) {
    echo "  * " .$script->getScriptParser()->getHumanReadable().PHP_EOL;
}
echo "-----------------------\n";

$interpreter = new LogicInterpreter();
$branches = $interpreter->astBranches($script);
foreach ($branches as $script) {
    echo "  * " .$script->getScriptParser()->getHumanReadable().PHP_EOL;
}
echo "-----------------------\n";