<?php

require_once "vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;

$directory = '/home/thomas/.bitcoin/blocks/';


$ec = Bitcoin::getEcAdapter();
$math = $ec->getMath();

$bhs = new \BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer();
$txs = new \BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer();
$bs = new \BitWasp\Bitcoin\Serializer\Block\HexBlockSerializer($math, $bhs, $txs);

$network = Bitcoin::getDefaultNetwork();
$bds = new \BitWasp\Bitcoin\Serializer\Block\BitcoindBlockSerializer($network, $bs);
$counter = 0;

$binary = file_get_contents($directory . '/blk00000.dat');
$buffer = new \BitWasp\Buffertools\Buffer($binary);
$parser = new \BitWasp\Buffertools\Parser($buffer);

$block = $bds->fromParser($parser);

echo $bds->serialize($block)->getBinary();