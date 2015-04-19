<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Collection;

class TransactionInputCollection extends Collection
{
    /**
     * @var TransactionInputInterface[]
     */
    private $inputs = [];

    /**
     * Initialize a new collection with a list of inputs.
     *
     * @param TransactionInputInterface[] $inputs
     */
    public function __construct(array $inputs = [])
    {
        $this->addInputs($inputs);
    }

    /**
     * Adds an input to the collection.
     *
     * @param TransactionInputInterface $input
     */
    public function addInput(TransactionInputInterface $input)
    {
        $this->inputs[] = $input;
    }

    /**
     * Adds a list of inputs to the collection.
     *
     * @param TransactionInputInterface[] $inputs
     */
    public function addInputs(array $inputs)
    {
        foreach ($inputs as $input) {
            $this->addInput($input);
        }
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
     * @return \BitWasp\Bitcoin\Transaction\TransactionInputCollection
     */
    public function slice($start, $length)
    {
        return new self(array_slice($this->inputs, $start, $length));
    }
}
