<?php

require_once "../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Serializer\Block\BlockSerializer;

if (!isset($argv[1])) {
    die("Enter the full path for your .bitcoin directory!\n"
    . "Usage: php ".$argv[0]." /home/you/.bitcoin/\n");
}

$directory = $argv[1] . ('/' === substr($argv[1], -1) ? '' : '/') . 'blocks/';

$ec = Bitcoin::getEcAdapter();
$math = $ec->getMath();

$network = Bitcoin::getDefaultNetwork();
$bds = new \BitWasp\Bitcoin\Serializer\Block\BitcoindBlockSerializer(
    $network,
    new BlockSerializer(
        $math,
        new BlockHeaderSerializer(),
        new TransactionSerializer()
    )
);
$counter = 0;

function name($counter) {
    return 'blk' . str_pad((string)$counter, 5, '0', STR_PAD_LEFT) . '.dat';
}

$blocks = [];

$c = 0;
$files = [];
if ($handle = opendir($directory)) {
    while (false !== ($entry = readdir($handle))) {
        if (substr($entry, 0, 3) === 'blk') {
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

foreach ($files as $entry) {
    echo " FILE: $entry\n";
    $try = true;
    $binary = file_get_contents($directory . '/' . $entry);
    $buffer = new \BitWasp\Buffertools\Buffer($binary);
    $parser = new \BitWasp\Buffertools\Parser($buffer);
    while ($try) {
        try {
            $block = $bds->fromParser($parser);
        } catch (\BitWasp\Buffertools\Exceptions\ParserOutOfRange $e) {
            echo "reached end of file - next!\n";
            $try = false;
        }
    }
    echo "finished loop\n";
}
