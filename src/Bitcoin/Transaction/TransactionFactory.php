<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;

class TransactionFactory
{
    /**
     * @return TransactionSerializer
     */
    public static function getSerializer()
    {
        $serializer = new TransactionSerializer;
        return $serializer;
    }

    /**
     * @param int|null $version
     * @param TransactionInputCollection|null $inputs
     * @param TransactionOutputCollection|null $outputs
     * @return Transaction
     */
    public static function create(
        $version = null,
        TransactionInputCollection $inputs = null,
        TransactionOutputCollection $outputs = null
    ) {
        if (null === $version) {
            $version = TransactionInterface::DEFAULT_VERSION;
        }

        $transaction = new Transaction($version, $inputs, $outputs);
        return $transaction;
    }

    /**
     * @param TransactionInterface $tx
     * @return TransactionBuilder
     */
    public static function builder(TransactionInterface $tx = null)
    {
        $tx = $tx ?: self::create();
        $builder = new TransactionBuilder(Bitcoin::getEcAdapter(), $tx);
        return $builder;
    }

    /**
     * @param $string
     * @return Transaction
     */
    public static function fromHex($string)
    {
        $serializer = self::getSerializer();
        $hex = $serializer->parse($string);
        return $hex;
    }
}
