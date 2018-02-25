<?php

require __DIR__ . "/../../../vendor/autoload.php";

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

$value = 100000000; /* amounts are satoshis */
$script = new Script(Buffer::hex("76a914b5a8a683e0f4f92fa2dc611b6d789cab964a104f88ac"));
$output = new TransactionOutput($value, $script);

echo "value       {$output->getValue()}\n";
echo "script hex: {$output->getScript()->getHex()}\n";
echo "       asm: {$output->getScript()->getScriptParser()->getHumanReadable()}\n";
