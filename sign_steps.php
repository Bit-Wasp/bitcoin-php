<?php

require "vendor/autoload.php";

use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Interpreter\Number;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Path\BranchInterpreter;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Crypto\Random\Random;

$privFactory = PrivateKeyFactory::compressed();
$random = new Random();

$alicePriv = $privFactory->generate($random);
$alicePub = $alicePriv->getPublicKey();

$bobPriv = $privFactory->generate($random);
$bobPub = $bobPriv->getPublicKey();

$rhash1 = $random->bytes(20);
$rhash2 = $random->bytes(20);

$script = ScriptFactory::sequence([
    Opcodes::OP_HASH160, Opcodes::OP_DUP, $rhash1, Opcodes::OP_EQUAL,
    Opcodes::OP_IF,
    Number::int(6000)->getBuffer(), Opcodes::OP_CHECKSEQUENCEVERIFY,
    Opcodes::OP_2DROP, $alicePub->getBuffer(),
    Opcodes::OP_ELSE,
    $rhash2, Opcodes::OP_EQUAL,
    Opcodes::OP_NOTIF,
    Number::int(6000)->getBuffer(), Opcodes::OP_CHECKLOCKTIMEVERIFY,
    Opcodes::OP_DROP,

    Opcodes::OP_ENDIF,
    $bobPub->getBuffer(),
    Opcodes::OP_ENDIF,
    Opcodes::OP_CHECKSIG
]);
echo $script->getHex().PHP_EOL;
$ast = new BranchInterpreter();
$branches = $ast->getScriptBranches($script);

foreach($branches as $branch) {
    echo "Branch \n";
    var_dump($branch->getPath());

    echo "Sign Steps \n\n";
    $steps = $branch->getSignSteps();
    foreach ($steps as $step) {
        echo " * " . $step->makeScript()->getScriptParser()->getHumanReadable() . PHP_EOL;
    }
    echo "Neutered script \n\n";
    echo $branch->getNeuteredScript()->getScriptParser()->getHumanReadable().PHP_EOL;
    echo "=======\n";
}
