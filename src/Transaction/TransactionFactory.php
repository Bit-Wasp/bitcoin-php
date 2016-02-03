<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Serializer\Transaction\NTransactionSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\Factory\TxSigner;
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
     * @param TransactionInterface $transaction
     * @param EcAdapterInterface|null $ecAdapter
     * @return TxSigner
     */
    public static function sign(TransactionInterface $transaction, EcAdapterInterface $ecAdapter = null)
    {
        return new TxSigner(
            $ecAdapter ?: Bitcoin::getEcAdapter(),
            $transaction
        );
    }

    /**
     * @param \BitWasp\Buffertools\Buffer|string $string
     * @return Transaction
     */
    public static function fromHex($string)
    {
        return (new NTransactionSerializer())->parse($string);
    }
}
