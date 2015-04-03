<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Collection;

class TransactionCollection extends Collection
{
    private $transactions = [];

    /**
     * Initialize a new collection with a list of transactions.
     *
     * @param TransactionInterface[] $transactions
     */
    public function __construct(array $transactions = [])
    {
        $this->addTransactions($transactions);
    }

    /**
     * Adds an input to the collection.
     *
     * @param TransactionInterface $transaction
     */
    public function addTransaction(TransactionInterface $transaction)
    {
        $this->transactions[] = $transaction;
    }

    /**
     * Adds a list of transactions to the collection.
     *
     * @param TransactionInterface[] $transactions
     */
    public function addTransactions(array $transactions)
    {
        foreach ($transactions as $transaction) {
            $this->addTransaction($transaction);
        }
    }

    /**
     * Gets an transaction at the given index.
     *
     * @param int $index
     * @throws \OutOfRangeException when $index is less than 0 or greater than the number of transactions.
     * @return TransactionInterface
     */
    public function getTransaction($index)
    {
        if ($index < 0 || $index >= count($this->transactions)) {
            throw new \OutOfRangeException();
        }

        return $this->transactions[$index];
    }

    /**
     * Returns all the transactions in the collection.
     *
     * @return TransactionInterface[]
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * (non-PHPdoc)
     * @see Countable::count()
     */
    public function count()
    {
        return count($this->transactions);
    }

    /**
     * Returns a new sliced collection
     *
     * @param int $start
     * @param int $length
     * @return \BitWasp\Bitcoin\Transaction\TransactionCollection
     */
    public function slice($start, $length)
    {
        return new self(array_slice($this->transactions, $start, $length));
    }
}
