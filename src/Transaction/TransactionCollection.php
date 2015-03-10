<?php

namespace Afk11\Bitcoin\Transaction;

class TransactionCollection implements \Countable
{
    private $transactions = [];

    /**
     * Initialize a new collection with a list of inputs.
     *
     * @param TransactionInterface[] $inputs
     */
    public function __construct(array $inputs = [])
    {
        $this->addTransactions($inputs);
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
     * Adds a list of inputs to the collection.
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
     * Gets an input at the given index.
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
     * Returns all the inputs in the collection.
     *
     * @return TransactionInputInterface[]
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
     * @return \Afk11\Bitcoin\Transaction\TransactionCollection
     */
    public function slice($start, $length)
    {
        return new self(array_slice($this->transactions, $start, $length));
    }
}
