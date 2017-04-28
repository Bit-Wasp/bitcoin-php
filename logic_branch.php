<?php

require "vendor/autoload.php";

use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Path\AstFactory;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Path\BranchInterpreter;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Crypto\Random\Random;

$random = new Random();


$alice = PrivateKeyFactory::fromInt(1);
$bob = PrivateKeyFactory::fromInt(2);

$script = ScriptFactory::sequence([
    Opcodes::OP_DUP, Opcodes::OP_HASH160, $alice->getPubKeyHash(), Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG,
]);

$ast = new BranchInterpreter();
$branches = $ast->getScriptBranches($script);
print_R($branches);


