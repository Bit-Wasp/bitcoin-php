<?php

require_once "../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Chain\Difficulty;
use BitWasp\Bitcoin\Chain\BlockHashIndex;
use BitWasp\Bitcoin\Chain\BlockStorage;
use BitWasp\Bitcoin\Chain\BlockIndex;
use BitWasp\Bitcoin\Chain\Blockchain;
use BitWasp\Bitcoin\Utxo\UtxoSet;
use BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Serializer\Block\HexBlockSerializer;
use Doctrine\Common\Cache\ArrayCache;

if (!isset($argv[1])) {
    die("Enter the full path for your .bitcoin directory!\n"
    . "Usage: php ".$argv[0]." /home/you/.bitcoin/\n");
}

$directory = $argv[1] . ('/' == substr($argv[1], -1) ? '' : '/') . 'blocks/';

$ec = Bitcoin::getEcAdapter();
$math = $ec->getMath();

$network = Bitcoin::getDefaultNetwork();
$bds = new \BitWasp\Bitcoin\Serializer\Block\BitcoindBlockSerializer(
    $network,
    new HexBlockSerializer(
        $math,
        new HexBlockHeaderSerializer(),
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

$blockchain = new Blockchain(
    $math,
    new \BitWasp\Bitcoin\Block\Block(
        $math,
        new \BitWasp\Bitcoin\Block\BlockHeader(
            '1',
            '0000000000000000000000000000000000000000000000000000000000000000',
            '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b',
            1231006505,
            \BitWasp\Buffertools\Buffer::hex('1d00ffff'),
            2083236893
        )
    ),
    new BlockStorage(
        new ArrayCache()
    ),
    new BlockIndex(
        new BlockHashIndex(
            new ArrayCache()
        ),
        new \BitWasp\Bitcoin\Chain\BlockHeightIndex(
            new ArrayCache()
        )
    ),
    new UtxoSet(new ArrayCache())
);

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

        $blockchain->process($block);
        echo "  [height: " . $blockchain->currentHeight() . "]\n";
    }
    echo "finished loop\n";
}
