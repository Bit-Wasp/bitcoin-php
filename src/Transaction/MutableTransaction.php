<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;

class MutableTransaction extends Transaction implements MutableTransactionInterface
{
    /**
     * @param int|string $version
     * @param MutableTransactionInputCollection $inputs
     * @param MutableTransactionOutputCollection $outputs
     * @param int|string $locktime
     * @throws \Exception
     */
    public function __construct(
        $version = TransactionInterface::DEFAULT_VERSION,
        MutableTransactionInputCollection $inputs = null,
        MutableTransactionOutputCollection $outputs = null,
        $locktime = '0'
    ) {

        if (!is_numeric($version)) {
            throw new \InvalidArgumentException('Transaction version must be numeric');
        }

        if (!is_numeric($locktime)) {
            throw new \InvalidArgumentException('Transaction locktime must be numeric');
        }

        if (Bitcoin::getMath()->cmp($version, TransactionInterface::MAX_VERSION) > 0) {
            throw new \Exception('Version must be less than ' . TransactionInterface::MAX_VERSION);
        }

        $this->version = $version;
        $this->inputs = $inputs ?: new MutableTransactionInputCollection();
        $this->outputs = $outputs ?: new MutableTransactionOutputCollection();
        $this->locktime = $locktime;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->createTransactionId();
    }

    /**
     * Get the array of inputs in the transaction
     *
     * @return MutableTransactionInputCollection
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * Get Outputs
     *
     * @return MutableTransactionOutputCollection
     */
    public function getOutputs()
    {
        return $this->outputs;
    }

    /**
     * @param MutableTransactionInputCollection $inputs
     * @return $this
     */
    public function setInputs(MutableTransactionInputCollection $inputs)
    {
        $this->inputs = $inputs;
        return $this;
    }

    /**
     * @param MutableTransactionOutputCollection $outputs
     * @return $this
     */
    public function setOutputs(MutableTransactionOutputCollection $outputs)
    {
        $this->outputs = $outputs;
        return $this;
    }

    /**
     * Set Lock Time
     * @param int $locktime
     * @return $this
     * @throws \Exception
     */
    public function setLockTime($locktime)
    {
        if (Bitcoin::getMath()->cmp($locktime, TransactionInterface::MAX_LOCKTIME) > 0) {
            throw new \Exception('Locktime must be less than ' . TransactionInterface::MAX_LOCKTIME);
        }

        $this->locktime = $locktime;
        return $this;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return $this->createBuffer();
    }
}
