<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Collection;

class TransactionInputCollection extends Collection
{
    /**
     * @var TransactionInputInterface[]
     */
    protected $inputs;

    /**
     * Initialize a new collection with a list of inputs.
     *
     * @param TransactionInputInterface[] $inputs
     */
    public function __construct(array $inputs = [])
    {
        // array_map to force instanceof TransactionOutputInterface
        $this->inputs = array_map(function(TransactionInputInterface $input) { return $input; }, $inputs);
    }

    /**
     * Gets an input at the given index.
     *
     * @param int $index
     * @throws \OutOfRangeException when $index is less than 0 or greater than the number of inputs.
     * @return TransactionInputInterface
     */
    public function getInput($index)
    {
        if ($index < 0 || $index >= count($this->inputs)) {
            throw new \OutOfRangeException();
        }

        return $this->inputs[$index];
    }

    /**
     * Returns all the inputs in the collection.
     *
     * @return TransactionInputInterface[]
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * (non-PHPdoc)
     * @see Countable::count()
     */
    public function count()
    {
        return count($this->inputs);
    }

    /**
     * Returns a new sliced collection
     *
     * @param int $start
     * @param int $length
     * @return static
     */
    public function slice($start, $length)
    {
        return new static(array_slice($this->inputs, $start, $length));
    }

    public function makeImmutableCopy()
    {
        return new TransactionInputCollection($this->getInputs());
    }

    public function makeMutableCopy()
    {
        return new MutableTransactionInputCollection($this->getInputs());
    }
}
