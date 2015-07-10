<?php

namespace BitWasp\Bitcoin\Transaction;

interface MutableTransactionInterface extends AbstractTransactionInterface
{

    /**
     * Get the array of inputs in the transaction
     *
     * @return MutableTransactionInputCollection
     */
    public function getInputs();
    public function setInputs(MutableTransactionInputCollection $inputs);

    /**
     * Get Outputs
     *
     * @return MutableTransactionOutputCollection
     */
    public function getOutputs();
    public function setOutputs(MutableTransactionOutputCollection $outputs);

    /**
     * Set Lock Time
     * @param int $locktime
     * @return $this
     * @throws \Exception
     */
    public function setLockTime($locktime);
}
