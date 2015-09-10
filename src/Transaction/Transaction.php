<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
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
    private $lockTime;

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
        $this->lockTime = $locktime;
    }

    /**
     * @return Transaction
     */
    public function __clone()
    {
        $this->inputs = clone $this->inputs;
        $this->outputs = clone $this->outputs;
    }

    /**
     * @return Buffer
     */
    public function getTxHash()
    {
        return Hash::sha256d($this->getBuffer());
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->getTxHash()->flip()->getHex();
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
     * @param int $i
     * @return TransactionInputInterface
     */
    public function getInput($i)
    {
        return $this->inputs->getInput($i);
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
     * @param int $i
     * @return TransactionOutputInterface
     */
    public function getOutput($i)
    {
        return $this->outputs->getOutput($i);
    }

    /**
     * Get Lock Time
     *
     * @return int|string
     */
    public function getLockTime()
    {
        return $this->lockTime;
    }

    /**
     * Set Lock Time
     * @param int $lockTime
     * @return $this
     * @throws \Exception
     */
    public function setLockTime($lockTime)
    {
        if (Bitcoin::getMath()->cmp($lockTime, TransactionInterface::MAX_LOCKTIME) > 0) {
            throw new \Exception('Locktime must be less than ' . TransactionInterface::MAX_LOCKTIME);
        }

        $this->lockTime = $lockTime;
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
     * @return int|string
     */
    public function getValueOut()
    {
        $outputs = $this->outputs->getOutputs();
        $nOutputs = count($outputs);
        $math = Bitcoin::getMath();
        $value = 0;
        for ($i = 0; $i < $nOutputs; $i++) {
            $value = $math->add($value, $outputs[$i]->getValue());
        }
        return $value;
    }

    /**
     * @return bool
     */
    public function isCoinbase()
    {
        return count($this->inputs) == 1 && $this->getInput(0)->isCoinBase();
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new TransactionSerializer())->serialize($this);
    }
}
