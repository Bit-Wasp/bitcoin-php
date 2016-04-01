<?php

namespace BitWasp\Bitcoin\Collection\Transaction;

use BitWasp\Bitcoin\Collection\StaticCollection;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class TransactionCollection extends StaticCollection
{
    /**
     * Initialize a new collection with a list of transactions.
     *
     * @param TransactionInterface[] $transactions
     */
    public function __construct(array $transactions = [])
    {
        foreach ($transactions as $tx) {
            if (!$tx instanceof TransactionInterface) {
                throw new \InvalidArgumentException('Must provide TransactionInterface[] to TransactionCollection');
            }
        }

        $this->set = \SplFixedArray::fromArray($transactions, false);
    }

    public function __clone()
    {
        $this->set = \SplFixedArray::fromArray(array_map(
            function (TransactionInterface $tx) {
                return clone $tx;
            },
            $this->set->toArray()
        ));
    }

    /**
     * @return TransactionInterface
     */
    public function current()
    {
        return $this->set->current();
    }

    /**
     * @param int $offset
     * @return TransactionInterface
     */
    public function offsetGet($offset)
    {
        if (!$this->set->offsetExists($offset)) {
            throw new \OutOfRangeException('No offset found');
        }

        return $this->set->offsetGet($offset);
    }

    /**
     * Returns all the transactions in the collection.
     *
     * @return TransactionInterface[]
     */
    public function all()
    {
        return $this->set->toArray();
    }
}
