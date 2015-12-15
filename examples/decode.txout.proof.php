<?php

require "../vendor/autoload.php";

$proof = '0100000090f0a9f110702f808219ebea1173056042a714bad51b916cb6800000000000005275289558f51c9966699404ae2294730c3c9f9bda53523ce50e9b95e558da2fdb261b4d4c86041b1ab1bf930900000002bc56aae9c0b9a19d49250c9bf9bf90b3c1ee3ac9096410a1eb179e1e92f90a66201f4587ec86b58297edc2dd32d6fcd998aa794308aac802a8af3be0e081d674013d';

$deserializer = new \BitWasp\Bitcoin\Serializer\Block\FilteredBlockSerializer(new \BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer(), new \BitWasp\Bitcoin\Serializer\Block\PartialMerkleTreeSerializer());

echo "Parsing proof: \n" . $proof . "\n";
$filtered = $deserializer->parse($proof);
$header = $filtered->getHeader();
$tree = $filtered->getPartialTree();

$hashes = $tree->getHashes();
$matches = [];

echo
    " Block Information: " . PHP_EOL .
    "   Hash:        " . $header->getHash()->getHex() . PHP_EOL .
    "   Merkle Root: " . $header->getMerkleRoot()->getHex() . PHP_EOL .
    PHP_EOL .
    " Proof: " . PHP_EOL .
    "   Tx Count:    " . $tree->getTxCount() . PHP_EOL .
    "   Tree height: " . $tree->calcTreeHeight() . PHP_EOL .
    "   Hash count: " . counT($hashes) . PHP_EOL;
foreach ($hashes as $c=> $hash) {
    echo
    "      ($c) " . $hash->getHex() . "\n";
}
    echo "" . PHP_EOL;