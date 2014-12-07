<?php

namespace Bitcoin\Transaction;

/**
 * Interface TransactionInterface
 * @package Bitcoin
 */
interface TransactionInterface
{
    const MAX_VERSION  = 4294967296;
    const MAX_LOCKTIME = 4294967296;

    /**
     * Get the transaction ID
     *
     * @return mixed
     */
    public function getTransactionId();

    /**
     * Get the version of this transaction
     *
     * @return mixed
     */
    public function getVersion();

    /**
     * Get a particular input by it's $index
     *
     * @param $index
     * @return mixed
     */
    public function getInput($index);

    /**
     * Return an array of all inputs
     *
     * @return mixed
     */
    public function getInputs();

    /**
     * Get a particular output by it's $index
     *
     * @param $index
     * @return mixed
     */
    public function getOutput($index);

    /**
     * Return an array of all outputs
     *
     * @return mixed
     */
    public function getOutputs();

    /**
     * Return the locktime for this transaction
     *
     * @return mixed
     */
    public function getLockTime();

    /**
     * Get the network for this transaction
     *
     * @return mixed
     */
    public function getNetwork();
}
