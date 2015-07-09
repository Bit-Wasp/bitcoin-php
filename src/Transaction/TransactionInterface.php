<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\SerializableInterface;

interface TransactionInterface extends SerializableInterface
{

    const DEFAULT_VERSION = 1;

    /**
     * The version parameter is encoded as a uint32
     */

    const MAX_VERSION = '4294967295';

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
     * @return SignatureHashInterface
     */
    public function getSignatureHash();

    /**
     * Returns an exact clone of the current transaction
     *
     * @return TransactionInterface
     */
    public function makeImmutableCopy();

    /**
     * @return MutableTransactionInterface
     */
    public function makeMutableCopy();
}
