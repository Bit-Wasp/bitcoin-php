<?php

declare(strict_types=1);

use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Buffertools\Buffertools;

require __DIR__ . "/../vendor/autoload.php";

if ($argc > 1) {
    $txHex = $argv[1];
} else {
    $txHex = "0100000001e13241278d5dbd249891df669d4be4c96512890a4c76c011144a5dc751fa593701000000fdfe0000483045022100c45e2d2d8912039c7e8156fe31fdfde6dd16621699bad97ed7df22e04f053183022038e886df2272901ceb3a982835a0ebb646c1e5ef739ffe08f80027698a32dd6601483045022100855a889a94a4be12b0d56b38ee2270cae00124496628a79fa9a04b9fc798c68d0220553838e47da12cdcd250d83bf1ba91aa4cf09de7590af39beac8a3e866406693014c695221028f1517e5d95149114f866f84bea127e5d6a7e4e54602309900d0065059b48532210334eea53fc0b0a6261e7e31a71437924779c5460435ebf79428f204b3ef791e372103e8b3230be3109457a6874662eacfb4de96e0876318ff8d990450773976a3ce6b53aeffffffff02804a5d050000000017a9144e951cf05cdf421b1867b7990ad5bf8c3be6c9f087028898000000000017a9144aecb4cb3057831067272420194df741007a25db8700000000";
}

$fields = [];
$tx = TransactionFactory::fromHex($txHex);
$fields[] = [4*4, "version {$tx->getVersion()}"];
$fields[] = [4*Buffertools::numToVarInt(count($tx->getInputs()))->getSize(), 'nIn'];
if ($tx->hasWitness()) {
    $fields[] = [2, "segwit markers 0001"];
}
foreach ($tx->getInputs() as $input) {
    $script = $input->getScript();
    $scriptSize = $script->getBuffer()->getSize();
    $scriptVarInt = Buffertools::numToVarInt($scriptSize);
    $fields[] = [4*32, "\ttxid\t".$input->getOutPoint()->getTxId()->getHex()];
    $fields[] = [4*4, "\tvout\t".$input->getOutPoint()->getVout()];
    $fields[] = [4*$scriptVarInt->getSize(), "\tvarint\t" . $scriptVarInt->getHex()];
    $fields[] = [4*$scriptSize, "\tscript\t".$input->getScript()->getHex()];
    $fields[] = [4*4, "\tseq\t".$input->getSequence()];
}
$fields[] = [Buffertools::numToVarInt(count($tx->getOutputs()))->getSize(), 'nOut'];
foreach ($tx->getOutputs() as $output) {
    $script = $output->getScript();
    $scriptSize = $script->getBuffer()->getSize();
    $scriptVarInt = Buffertools::numToVarInt($scriptSize);
    $fields[] = [4*8, "\tvalue\t{$output->getValue()}"];
    $fields[] = [4*$scriptVarInt->getSize(), "\tvarint {$scriptVarInt->getHex()}\t"];
    $fields[] = [4*$scriptSize, "\tscript\n{$script->getHex()}"];
}
if ($tx->hasWitness()) {
    for ($i = 0; $i < count($tx->getInputs()); $i++) {
        $wit = $tx->getWitness($i);
        $fields[] = [Buffertools::numToVarInt(count($wit))->getSize(), "wit {$i}"];
        if ($wit->count() > 0) {
            foreach ($wit->all() as $value) {
                $fields[] = [Buffertools::numToVarInt($value->getSize())->getSize() + $value->getSize(), "\tvalue\t{$value->getHex()}"];
            }
        }
    }
}
$fields[] = [4*4, "lockTime {$tx->getLockTime()}"];

$totalIn = 0;
foreach ($fields as $field) {
    $totalIn += $field[0];
    echo "{$field[0]}\t{$field[1]}\n";
}
echo "$totalIn\tTOTAL WEIGHT\n";
echo ($totalIn/4)."\tTOTAL VSIZE\n";
