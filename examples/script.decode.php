<?php

require_once "../vendor/autoload.php";

use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Transaction;

$script = ScriptFactory::fromHex($argv[1]);
print_r($script->getScriptParser()->getHumanReadable());