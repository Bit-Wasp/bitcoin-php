<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Transaction\TransactionCollection;


class TransactionCollectionSerializer
{
    /**
     * @var TransactionSerializer
     */
    private $serializer;

    /**
     * @param TransactionSerializer $serializer
     */
    public function __construct(TransactionSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param TransactionCollection $transactions
     * @return array
     */
    public function serialize(TransactionCollection $transactions)
    {
        $array = array();
        foreach ($transactions->getTransactions() as $input) {
            $array[] = $this->serializer->serialize($input);
        }
        return $array;
    }

    /**
     * @param Parser $parser
     * @return TransactionCollection
     */
    public function fromParser(Parser & $parser)
    {
        $transactions = new TransactionCollection;
        $transactions->addTransactions(
            $parser->getArray(
                function () use (&$parser) {
                    $transaction = $this->serializer->fromParser($parser);
                    return $transaction;
                }
            )
        );
        return $transactions;
    }

    /**
     * @param $string
     * @return TransactionCollection
     */
    public function parse($string)
    {
        $parser = new Parser($string);
        $inputs = $this->fromParser($parser);
        return $inputs;
    }
}
