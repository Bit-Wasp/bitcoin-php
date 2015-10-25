<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\Transaction\SignatureHash\Hasher;
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

        $math = Bitcoin::getMath();
        if ($math->cmp($version, TransactionInterface::MAX_VERSION) > 0) {
            throw new \Exception('Version must be less than ' . TransactionInterface::MAX_VERSION);
        }

        if ($math->cmp($locktime, TransactionInterface::MAX_LOCKTIME) > 0) {
            throw new \Exception('Locktime must be less than ' . TransactionInterface::MAX_LOCKTIME);
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
     * @return Buffer
     */
    public function getTxId()
    {
        return $this->getTxHash()->flip();
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
     * @param int $index
     * @return TransactionInputInterface
     */
    public function getInput($index)
    {
        return $this->inputs->get($index);
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
     * @param int $index
     * @return TransactionOutputInterface
     */
    public function getOutput($index)
    {
        return $this->outputs->get($index);
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
     * @return Hasher
     */
    public function getSignatureHash()
    {
        return new Hasher($this);
    }

    /**
     * @return int|string
     */
    public function getValueOut()
    {
        $math = Bitcoin::getMath();
        $value = 0;
        foreach ($this->outputs as $output) {
            $value = $math->add($value, $output->getValue());
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
