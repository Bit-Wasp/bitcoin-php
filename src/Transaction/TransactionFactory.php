<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\Mutator\TxMutator;

class TransactionFactory
{
    /**
     * @return TxBuilder
     */
    public static function build()
    {
        return new TxBuilder();
    }

    /**
     * @param TransactionInterface $transaction
     * @return TxMutator
     */
    public static function mutate(TransactionInterface $transaction)
    {
        return new TxMutator($transaction);
    }

    /**
     * @param \BitWasp\Buffertools\BufferInterface|string $string
     * @return TransactionInterface
     */
    public static function fromHex($string)
    {
        return (new TransactionSerializer())->parse($string);
    }
}
