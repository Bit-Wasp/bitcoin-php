<?php

namespace BitWasp\Bitcoin\Transaction;


class MutableTransactionInputCollection extends TransactionInputCollection
{
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
     * (over)write an input to the collection.
     *
     * @param int                       $i
     * @param TransactionInputInterface $input
     */
    public function replaceInput($i, TransactionInputInterface $input)
    {
        if (!isset($this->inputs[$i])) {
            throw new \InvalidArgumentException();
        }

        $this->inputs[$i] = $input;
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
}
