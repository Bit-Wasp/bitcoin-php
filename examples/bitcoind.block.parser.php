<?php

require_once "../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;

if (!isset($argv[1])) {
    die("Enter the full path for your .bitcoin directory!\n"
    . "Usage: php ".$argv[0]." /home/you/.bitcoin/\n");
}

$directory = $argv[1] . ('/' == substr($argv[1], -1) ? '' : '/') . 'blocks/';

$ec = Bitcoin::getEcAdapter();
$math = $ec->getMath();

$bhs = new \BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer();
$txs = new \BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer();
$bs = new \BitWasp\Bitcoin\Serializer\Block\HexBlockSerializer($math, $bhs, $txs);

$network = Bitcoin::getDefaultNetwork();
$bds = new \BitWasp\Bitcoin\Serializer\Block\BitcoindBlockSerializer($network, $bs);
$counter = 0;

function name($counter) {
    return 'blk' . str_pad((string)$counter, 5, '0', STR_PAD_LEFT) . '.dat';
}

$blocks = [];

$c = 0;
$files = [];
if ($handle = opendir($directory)) {
    while (false !== ($entry = readdir($handle))) {
        if (substr($entry, 0, 3) == 'blk') {
            $files[] = $entry;
        }
    }

    closedir($handle);
}

usort($files, function ($a, $b) {
    $intA = (int)substr($a, 3, -4);
    $intB = (int)substr($b, 3, -4);
    return gmp_cmp($intA, $intB);
});

$difficulty  = new \BitWasp\Bitcoin\Chain\Difficulty($math);
$utxoSet = new \BitWasp\Bitcoin\Utxo\UtxoSet();
$blockchain = new \BitWasp\Bitcoin\Chain\Blockchain($difficulty, $utxoSet);

$utxo = 0;
$bytes = 0;

function out($bytes) {
    if ($bytes < 1024) {
        return $bytes . "b";
    }
    if ($bytes < pow(1024, 2)) {
        return $bytes/1024 . 'kb';
    }
    if ($bytes < pow(1024, 3)) {
        return $bytes/1024^2 . 'mb';
    }
    if ($bytes < pow(1024, 4)) {
        return $bytes/1024^3 . 'gb';
    }
    return $bytes .' b';
}

foreach ($files as $entry) {
    echo " FILE: $entry\n";
    $try = true;
    $binary = file_get_contents($directory . '/' . $entry);
    $buffer = new \BitWasp\Buffertools\Buffer($binary);
    $parser = new \BitWasp\Buffertools\Parser($buffer);
    while ($try) {
        try {
            $block = $bds->fromParser($parser);
            $bytes += $block->getBuffer()->getSize();
            $blockchain->add($block);

            $utxo += count($block->getTransactions());
            echo "   " . $c . " - " .  $block->getHeader()->getBlockHash() . " - (d: ".$blockchain->getChainDifficulty() . ") (u: $utxo) (size: ".out($bytes).") \n";

            $c++;
        } catch (\Exception $e) {
            $try = false;
        }
    }
}
