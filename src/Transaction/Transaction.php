<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;

class Transaction extends Serializable implements TransactionInterface
{
    /**
     * @var int|string
     */
    private $version;

    /**
     * @var TransactionInputCollection
     */
    private $inputs;

    /**
     * @var TransactionOutputCollection
     */
    private $outputs;

    /**
     * @var int|string
     */
    private $locktime;

    /**
     * @param int|string $version
     * @param TransactionInputCollection $inputs
     * @param TransactionOutputCollection $outputs
     * @param int|string $locktime
     * @throws \Exception
     */
    public function __construct(
        $version = TransactionInterface::DEFAULT_VERSION,
        TransactionInputCollection $inputs = null,
        TransactionOutputCollection $outputs = null,
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
        $this->inputs = $inputs ?: new TransactionInputCollection();
        $this->outputs = $outputs ?: new TransactionOutputCollection();
        $this->locktime = $locktime;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        $hash = bin2hex(Buffertools::flipBytes(Hash::sha256d($this->getBuffer())));

        return $hash;
    }

    /**
     * @inheritdoc
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param TransactionInputCollection $inputs
     * @return $this
     */
    public function setInputs(TransactionInputCollection $inputs)
    {
        $this->inputs = $inputs;
        return $this;
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
     * @param TransactionOutputCollection $outputs
     * @return $this
     */
    public function setOutputs(TransactionOutputCollection $outputs)
    {
        $this->outputs = $outputs;
        return $this;
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
     * @return SignatureHash
     */
    public function getSignatureHash()
    {
        return new SignatureHash($this);
    }

    /**
     * @return Transaction
     */
    public function makeCopy()
    {
        return new Transaction(
            $this->getVersion(),
            new TransactionInputCollection(
                array_map(
                    function (TransactionInputInterface $value) {
                        return new TransactionInput(
                            $value->getTransactionId(),
                            $value->getVout(),
                            clone $value->getScript(),
                            $value->getSequence()
                        );
                    },
                    $this->getInputs()->getInputs()
                )
            ),
            new TransactionOutputCollection(
                array_map(
                    function (TransactionOutputInterface $value) {
                            return new TransactionOutput(
                                $value->getValue(),
                                clone $value->getScript()
                            );
                    },
                    $this->getOutputs()->getOutputs()
                )
            ),
            $this->getLockTime()
        );
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        $serializer = new TransactionSerializer();
        $hex = $serializer->serialize($this);
        return $hex;
    }
}
