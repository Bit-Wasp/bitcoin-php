<?php

namespace Bitcoin\Transaction;

/**
 * Interface TransactionInterface
 * @package Bitcoin
 */
interface TransactionInterface
{

    const DEFAULT_VERSION = 1;

    /**
     * The version parameter is encoded as a uint32
     */

    const MAX_VERSION  = '4294967295';

    /**
     * The locktime parameter is encoded as a uint32
     */
    const MAX_LOCKTIME = '4294967295';

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
     * Return an array of all inputs
     *
     * @return TransactionInputCollection
     */
    public function getInputs();

    /**
     * Return an array of all outputs
     *
     * @return TransactionOutputCollection
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
