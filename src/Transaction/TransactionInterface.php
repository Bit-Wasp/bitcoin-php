<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\SerializableInterface;
use BitWasp\Buffertools\Buffer;

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
     * @return string
     */
    public function getTransactionId();

    /**
     * Get the transactions sha256d hash.
     *
     * @return Buffer
     */
    public function getTxHash();

    /**
     * Get the version of this transaction
     *
     * @return int|string
     */
    public function getVersion();

    /**
     * Return an array of all inputs
     *
     * @return TransactionInputCollection
     */
    public function getInputs();
    public function setInputs(TransactionInputCollection $inputs);

    /**
     * Return an array of all outputs
     *
     * @return TransactionOutputCollection
     */
    public function getOutputs();
    public function setOutputs(TransactionOutputCollection $outputs);
    /**
     * Return the locktime for this transaction
     *
     * @return int|string
     */
    public function getLockTime();

    /**
     * @return SignatureHash
     */
    public function getSignatureHash();
}
