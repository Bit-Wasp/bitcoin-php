<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Signature\SignatureHash;

class Transaction extends Serializable implements TransactionInterface
{
    /**
     * @var int
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

    /**
     * @param int $version
     * @param string $locktime
     * @param TransactionInputCollection $inputs
     * @param TransactionOutputCollection $outputs
     * @throws \Exception
     */
    public function __construct(
        $version = TransactionInterface::DEFAULT_VERSION,
        TransactionInputCollection $inputs = null,
        TransactionOutputCollection $outputs = null
    ) {
        if (Bitcoin::getMath()->cmp($version, TransactionInterface::MAX_VERSION) > 0) {
            throw new \Exception('Version must be less than ' . TransactionInterface::MAX_VERSION);
        }

        $this->version = $version;
        $this->inputs = $inputs ?: new TransactionInputCollection();
        $this->outputs = $outputs ?: new TransactionOutputCollection();
    }

    /**
     * @return Parser|string
     */
    public function getTransactionId()
    {
        $hash = Hash::sha256d($this->getBuffer());

        $txid = new Parser();
        $txid = $txid
            ->writeBytes(32, $hash, true)
            ->getBuffer()
            ->getHex();

        return $txid;
    }

    /**
     * @inheritdoc
     */
    public function getVersion()
    {
        if (is_null($this->version)) {
            return TransactionInterface::DEFAULT_VERSION;
        }

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
        if ($this->locktime === null) {
            return '0';
        }

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
    public function signatureHash()
    {
        return new SignatureHash($this);
    }

    /**
     * Return the transaction in the format of an array compatible with bitcoind.
     *
     * @return array
     */
    public function toArray()
    {
        $inputs = array_map(function (TransactionInputInterface $input) {
            return array(
                'txid' => $input->getTransactionId(),
                'vout' => $input->getVout(),
                'scriptSig' => $input->getScript()->toArray()
            );
        }, $this->getInputs()->getInputs());

        $outputs = array_map(function (TransactionOutputInterface $output) {
            return array(
                'value' => $output->getValue(),
                'scriptPubKey' => $output->getScript()->toArray()
            );
        }, $this->getOutputs()->getOutputs());

        return array(
            'txid' => $this->getTransactionId(),
            'version' => $this->getVersion(),
            'locktime' => $this->getLockTime(),
            'vin' => $inputs,
            'vout' => $outputs
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
