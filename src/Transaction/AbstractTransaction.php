<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;

abstract class AbstractTransaction extends Serializable implements AbstractTransactionInterface
{
    /**
     * @var int|string
     */
    protected $version;

    /**
     * @var TransactionInputCollection
     */
    protected $inputs;

    /**
     * @var TransactionOutputCollection
     */
    protected $outputs;

    /**
     * @var int|string
     */
    protected $locktime;

    protected function createTransactionId() {
        return bin2hex(Buffertools::flipBytes(Hash::sha256d($this->getBuffer())));
    }

    /**
     * @inheritdoc
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Get the array of inputs in the transaction
     *
     * @return TransactionInputCollection
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * Get Outputs
     *
     * @return TransactionOutputCollection
     */
    public function getOutputs()
    {
        return $this->outputs;
    }

    /**
     * Get Lock Time
     *
     * @return int|string
     */
    public function getLockTime()
    {
        return $this->locktime;
    }

    /**
     * @return SignatureHash
     */
    public function getSignatureHash()
    {
        return new SignatureHash($this);
    }

    /**
     * @return TransactionInterface
     */
    public function makeImmutableCopy()
    {
        return new Transaction(
            $this->getVersion(),
            $this->getInputs()->makeImmutableCopy(),
            $this->getOutputs()->makeImmutableCopy(),
            $this->getLockTime()
        );
    }

    /**
     * @return MutableTransactionInterface
     */
    public function makeMutableCopy()
    {
        return new MutableTransaction(
            $this->getVersion(),
            $this->getInputs()->makeMutableCopy(),
            $this->getOutputs()->makeMutableCopy(),
            $this->getLockTime()
        );
    }

    protected function createBuffer()
    {
        $serializer = new TransactionSerializer();
        $raw = $serializer->serialize($this);

        return $raw;
    }
}
