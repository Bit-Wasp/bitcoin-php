<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 05:09
 */

namespace Bitcoin;


class Transaction implements TransactionInterface
{
    protected $network;
    protected $version;
    protected $inputs = array();
    protected $outputs = array();
    protected $locktime;

    public function __construct(NetworkInterface $network) {
        $this->network = $network;
    }

    public function getNetwork()
    {
        return $this->network;
    }

    public function getTransactionId()
    {
        // TODO
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version)
    {
        if (empty($version) OR !is_numeric($version)) {
            throw new \Exception('Version must be a decimal');
        }

        if ($version > TransactionInterface::MAX_VERSION) {
            throw new \Exception('Version must be less than ' . TransactionInterface::MAX_VERSION);
        }

        $this->version = $version;

        return $this;
    }

    public function addInput(TransactionInput $input)
    {
        if (!empty($input)) {
            $this->inputs[] = $input;
        }

        return $this;
    }

    public function getInput($index)
    {
        if (!isset($this->inputs[$index])) {
            throw new Exception('Input at this index does not exist');
        }

        return $this->inputs[$index];
    }

    public function getInputs()
    {
        return $this->inputs;
    }

    public function addOutput($index, TransactionOutput $output)
    {
        if (!empty($output)) {
            $this->outputs[$index] = $output;
        }

        return $this;
    }

    public function getOutput($index)
    {
        if (!isset($this->outputs[$index])) {
            throw new Exception('Output at this index does not exist');
        }

        return $this->outputs[$index];
    }

    public function getOutputs()
    {
        return $this->outputs;
    }

    public function getLockTime()
    {
        return $this->locktime;
    }

    public function setLockTime($locktime = 0) {

        if(empty($locktime) OR !is_numeric($locktime)){
            throw new \Exception('Locktime must be a decimal');
        }

        if ($locktime > TransactionInterface::MAX_LOCKTIME) {
            throw new \Exception('Locktime must be less than ' . TransactionInterface::MAX_LOCKTIME);
        }

        $this->locktime = $locktime;
        return $this;
    }

    public function serialize() {
        // TODO
    }
}