<?php

require "vendor/autoload.php";

use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Interpreter\Number;
use BitWasp\Bitcoin\Script\Path\AstFactory;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Path\BranchInterpreter;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Crypto\Random\Random;

$alice = PrivateKeyFactory::fromInt(1);
$bob = PrivateKeyFactory::fromInt(2);

$random = new Random();

$alicePriv = PrivateKeyFactory::create(true);
$alicePub = $alicePriv->getPublicKey();

$bobPriv = PrivateKeyFactory::create(true);
$bobPub = $bobPriv->getPublicKey();

$rhash1 = $alicePub->getPubKeyHash();
$rhash2 = $bobPub->getPubKeyHash();

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

$ast = new BranchInterpreter();
$branches = $ast->getScriptBranches($script);
foreach ($branches as $branch) {
    var_dump($branch->getBranchDescriptor());
    foreach ($branch->getSegments() as $segment) {
        echo " * " . $segment->makeScript()->getScriptParser()->getHumanReadable() . PHP_EOL;
    }
}
//print_r($branches);
