<?php

namespace BitWasp\Bitcoin\Transaction;

class MutableTransactionOutputCollection extends TransactionOutputCollection
{
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
     * (over)write an output to the collection.
     *
     * @param int                       $i
     * @param TransactionOutputInterface $output
     */
    public function setOutput($i, TransactionOutputInterface $output)
    {
        $this->outputs[$i] = $output;
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
}
