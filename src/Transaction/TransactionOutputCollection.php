<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Collection;

class TransactionOutputCollection extends Collection
{
    /**
     * @var TransactionOutput[]
     */
    private $outputs = [];

    /**
     * Initialize a new collection with a list of outputs.
     *
     * @param TransactionOutputInterface[] $outputs
     */
    public function __construct(array $outputs = [])
    {
        $this->addOutputs($outputs);
    }

    /**
     * Adds an output to the collection.
     *
     * @param TransactionOutputInterface $output
     */
    public function addOutput(TransactionOutputInterface $output)
    {
        $this->outputs[] = $output;
    }

    /**
     * Adds a list of outputs to the collection
     *
     * @param TransactionOutputInterface[] $outputs
     */
    public function addOutputs(array $outputs)
    {
        foreach ($outputs as $output) {
            $this->addOutput($output);
        }
    }

    /**
     * Gets an output at a given index.
     *
     * @param int $index
     * @throws \OutOfRangeException when index is less than 0 or greater than the number of outputs.
     * @return TransactionOutputInterface
     */
    public function getOutput($index)
    {
        if ($index < 0 || $index >= count($this->outputs)) {
            throw new \OutOfRangeException();
        }

        return $this->outputs[$index];
    }

    /**
     * Returns all the outputs in collection.
     *
     * @return TransactionOutputInterface[]
     */
    public function getOutputs()
    {
        return $this->outputs;
    }

    /**
     * (non-PHPdoc)
     * @see Countable::count()
     */
    public function count()
    {
        return count($this->outputs);
    }

    /**
     * Returns a new sliced collection
     *
     * @param int $start
     * @param int $length
     * @return \BitWasp\Bitcoin\Transaction\TransactionOutputCollection
     */
    public function slice($start, $length)
    {
        return new self(array_slice($this->outputs, $start, $length));
    }
}
