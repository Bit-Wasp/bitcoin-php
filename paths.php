<?php

require "vendor/autoload.php";

use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Interpreter\Number;
use BitWasp\Bitcoin\Script\Path\LogicInterpreter;
use BitWasp\Bitcoin\Script\Path\PathInterpreter;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Crypto\Random\Random;

$alice = PrivateKeyFactory::fromInt(1);
$bob = PrivateKeyFactory::fromInt(2);

$random = new Random();

$alicePriv = \BitWasp\Bitcoin\Key\PrivateKeyFactory::create(true);
$alicePub = $alicePriv->getPublicKey();

$bobPriv = \BitWasp\Bitcoin\Key\PrivateKeyFactory::create(true);
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

$logic = new LogicInterpreter();
$branches = $logic->getSegments($script);

print_R($branches);

die();
$interpreter = new PathInterpreter();
$flags = Interpreter::VERIFY_MINIMALDATA;


echo 'true' . PHP_EOL;
$script3 = ScriptFactory::fromOperations($interpreter->getBranch($script, [true], $flags));
echo $script3->getScriptParser()->getHumanReadable() . PHP_EOL;

echo 'false false' . PHP_EOL;
$script2 = ScriptFactory::fromOperations($interpreter->getBranch($script, [false, false], $flags));
echo $script2->getScriptParser()->getHumanReadable() . PHP_EOL;


echo 'false true' . PHP_EOL;
$script1 = ScriptFactory::fromOperations($interpreter->getBranch($script, [false, true], $flags));
echo $script1->getScriptParser()->getHumanReadable() . PHP_EOL;

