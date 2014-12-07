<?php

namespace Bitcoin\Transaction;

/**
 * Interface TransactionInterface
 * @package Bitcoin
 */
interface TransactionInterface
{
    /**
     * The version parameter is encoded as a uint32
     */
    const MAX_VERSION  = 4294967296;

    /**
     * The locktime parameter is encoded as a uint32
     */
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
     * Return a reference to the internal array containing the inputs
     *
     * @return array
     */
    public function &getInputsReference();

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
     * Return a reference to the internal array containing the outputs
     *
     * @return mixed
     */
    public function &getOutputsReference();

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
