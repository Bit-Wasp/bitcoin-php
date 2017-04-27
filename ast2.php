<?php

require "vendor/autoload.php";

use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Path\AstFactory;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Crypto\Random\Random;

$random = new Random();


$alice = PrivateKeyFactory::fromInt(1);
$bob = PrivateKeyFactory::fromInt(2);

$script = ScriptFactory::sequence([
    Opcodes::OP_IF,

    Opcodes::OP_DUP, Opcodes::OP_HASH160, $alice->getPubKeyHash(), Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG,

    Opcodes::OP_ELSE,

    Opcodes::OP_DUP, Opcodes::OP_HASH160, $bob->getPubKeyHash(), Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG,

    Opcodes::OP_ENDIF
]);

$ast = new AstFactory($script);
$branches = $ast->getScriptBranches();
print_r($branches);