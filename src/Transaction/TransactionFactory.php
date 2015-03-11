<?php

namespace Afk11\Bitcoin\Transaction;

use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Serializer\Transaction\TransactionSerializer;

class TransactionFactory
{
    /**
     * @return Transaction
     */
    public static function create()
    {
        $transaction = new Transaction;
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

    /**
     * @param Parser $parser
     * @return Transaction
     */
    public static function fromParser(Parser &$parser)
    {
        $serializer = new TransactionSerializer;
        $hex = $serializer->fromParser($parser);
        return $hex;
    }
}
