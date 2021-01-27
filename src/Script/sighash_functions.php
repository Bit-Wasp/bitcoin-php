<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializerInterface;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

/**
 * @param OutPointSerializerInterface $outPointSerializer
 * @param TransactionInterface $tx
 * @return BufferInterface
 */
function SighashGetPrevoutsSha256(OutPointSerializerInterface $outPointSerializer, TransactionInterface $tx): BufferInterface
{
    $binary = '';
    foreach ($tx->getInputs() as $input) {
        $binary .= $outPointSerializer->serialize($input->getOutPoint())->getBinary();
    }
    return Hash::sha256(new Buffer($binary));
}

/**
 * @param OutPointSerializerInterface $outPointSerializer
 * @param TransactionInterface $tx
 * @return BufferInterface
 */
function SighashGetSequencesSha256(TransactionInterface $tx): BufferInterface
{
    $binary = '';
    foreach ($tx->getInputs() as $input) {
        $binary .= pack("V", $input->getSequence());
    }
    return Hash::sha256(new Buffer($binary));
}

/**
 * @param TransactionOutputSerializer $txOutSerializer
 * @param TransactionInterface $tx
 * @return BufferInterface
 */
function SighashGetOutputsSha256(TransactionOutputSerializer $txOutSerializer, TransactionInterface $tx): BufferInterface
{
    $binary = '';
    foreach ($tx->getOutputs() as $output) {
        $binary .= $txOutSerializer->serialize($output)->getBinary();
    }
    return Hash::sha256(new Buffer($binary));
}

/**
 * @param TransactionOutputSerializer $txOutSerializer
 * @param TransactionOutputInterface[] $spentTxOuts
 * @return BufferInterface
 */
function SighashGetSpentAmountsHash(TransactionOutputSerializer $txOutSerializer, array $spentTxOuts): BufferInterface
{
    $amounts = [];
    $count = count($spentTxOuts);
    for ($i = 0; $i < $count; $i++) {
        $amounts[] = $spentTxOuts[$i]->getValue();
    }
    return Hash::sha256(new Buffer(pack(str_repeat("P", $count), ...$amounts)));
}
