<?php

namespace Bitcoin;

/**
 * Class Transaction
 * @package Bitcoin
 */
class Transaction implements TransactionInterface
{
    /**
     * @var NetworkInterface
     */
    protected $network;

    /**
     * @var
     */
    protected $version;

    /**
     * @var array
     */
    protected $inputs = array();

    /**
     * @var array
     */
    protected $outputs = array();

    /**
     * @var
     */
    protected $locktime;

    /**
     * @param NetworkInterface $network
     */
    public function __construct(NetworkInterface $network)
    {
        $this->network = $network;
    }

    /**
     * @inheritdoc
     */
    public function getNetwork()
    {
        return $this->network;
    }

    /**
     * @inheritdoc
     */
    public function getTransactionId()
    {
        // TODO
    }

    /**
     * @inheritdoc
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the version of the transaction
     *
     * @param $version
     * @return $this
     * @throws \Exception
     */
    public function setVersion($version)
    {
        if (empty($version) or !is_numeric($version)) {
            throw new \Exception('Version must be a decimal');
        }

        if ($version > TransactionInterface::MAX_VERSION) {
            throw new \Exception('Version must be less than ' . TransactionInterface::MAX_VERSION);
        }

        $this->version = $version;

        return $this;
    }

    /**
     * Add an input to this transaction
     *
     * @param TransactionInput $input
     * @return $this
     */
    public function addInput(TransactionInput $input)
    {
        if (!empty($input)) {
            $this->inputs[] = $input;
        }

        return $this;
    }

    /**
     * Get a specific input by it's index in the array
     *
     * @param $index
     * @return mixed
     * @throws \Exception
     */
    public function getInput($index)
    {
        if (!isset($this->inputs[$index])) {
            throw new \Exception('Input at this index does not exist');
        }

        return $this->inputs[$index];
    }

    /**
     * Get the array of inputs in the transaction
     *
     * @return array
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * Add an output at a specific index
     *
     * @param $index
     * @param TransactionOutput $output
     * @return $this`
     */
    public function addOutput($index, TransactionOutput $output)
    {
        if (!empty($output)) {
            $this->outputs[$index] = $output;
        }

        return $this;
    }

    /**
     * Get an output at the specific index
     *
     * @param $index
     * @return mixed
     * @throws \Exception
     */
    public function getOutput($index)
    {
        if (!isset($this->outputs[$index])) {
            throw new \Exception('Output at this index does not exist');
        }

        return $this->outputs[$index];
    }

    /**
     * Get Outputs
     *
     * @return array
     */
    public function getOutputs()
    {
        return $this->outputs;
    }

    /**
     * Get Lock Time
     *
     * @return mixed
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
    public function setLockTime($locktime = 0)
    {
        if (empty($locktime) or !is_numeric($locktime)) {
            throw new \Exception('Locktime must be a decimal');
        }

        if ($locktime > TransactionInterface::MAX_LOCKTIME) {
            throw new \Exception('Locktime must be less than ' . TransactionInterface::MAX_LOCKTIME);
        }

        $this->locktime = $locktime;
        return $this;
    }

    public function serialize()
    {
        // TODO
    }
}
