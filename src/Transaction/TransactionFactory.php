<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;

class TransactionFactory
{
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
     * @param $string
     * @return Transaction
     */
    public static function fromHex($string)
    {
        $serializer = new TransactionSerializer;
        $hex = $serializer->parse($string);
        return $hex;
    }
}
