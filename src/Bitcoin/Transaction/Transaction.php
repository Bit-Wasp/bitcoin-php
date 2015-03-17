<?php

namespace Afk11\Bitcoin\Transaction;

use \Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Crypto\Random\Random;
use Afk11\Bitcoin\Crypto\Random\RbgInterface;
use \Afk11\Bitcoin\Key\PrivateKeyInterface;
use \Afk11\Bitcoin\Parser;
use \Afk11\Bitcoin\Crypto\Hash;
use Afk11\Bitcoin\Network\NetworkInterface;
use Afk11\Bitcoin\Serializable;
use Afk11\Bitcoin\Serializer\Transaction\TransactionSerializer;
use \Afk11\Bitcoin\Signature\Signature;
use \Afk11\Bitcoin\Signature\SignatureHash;
use Afk11\Bitcoin\Signature\Signer;

class Transaction extends Serializable implements TransactionInterface
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
     * @var TransactionInputCollection
     */
    protected $inputs = null;

    /**
     * @var TransactionOutputCollection
     */
    protected $outputs = null;

    /**
     * @var
     */
    protected $locktime;

    /**
     * @internal param NetworkInterface $network
     */
    public function __construct()
    {
        $this->inputs = new TransactionInputCollection();
        $this->outputs = new TransactionOutputCollection();
    }

    /**
     * @inheritdoc
     */
    public function getTransactionId()
    {
        $hash = Hash::sha256d($this->getBuffer());

        $txid = new Parser();
        $txid = $txid
            ->writeBytes(32, $hash, true)
            ->getBuffer()
            ->serialize('hex');

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
     * Set the version of the transaction
     *
     * @param $version
     * @return $this
     * @throws \Exception
     */
    public function setVersion($version)
    {
        if (Bitcoin::getMath()->cmp($version, TransactionInterface::MAX_VERSION) > 0) {
            throw new \Exception('Version must be less than ' . TransactionInterface::MAX_VERSION);
        }

        $this->version = $version;
        return $this;
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
     * @return mixed
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
     * @param PrivateKeyInterface $privateKey
     * @param TransactionOutputInterface $txOut
     * @param $inputToSign
     * @param RbgInterface $random
     * @return Signature
     * @throws \Exception
     */
    public function sign(PrivateKeyInterface $privateKey, TransactionOutputInterface $txOut, $inputToSign, RbgInterface $random = null)
    {
        $hash = $this->signatureHash()->calculate($txOut->getScript(), $inputToSign);

        if (is_null($random)) {
            $random = new Random();
        }

        $signer = new Signer(Bitcoin::getMath(), Bitcoin::getGenerator());
        $sig = $signer->sign($privateKey, $hash, $random);

        return $sig;
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
