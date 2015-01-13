<?php

namespace Bitcoin\Transaction;

use Bitcoin\Bitcoin;
use Bitcoin\Key\PrivateKeyInterface;
use Bitcoin\SerializableInterface;
use Bitcoin\Util\Parser;
use Bitcoin\Crypto\Hash;
use Bitcoin\NetworkInterface;
use Bitcoin\Signature\Signature;
use Bitcoin\Signature\SignatureHash;
use Bitcoin\Signature\K\KInterface;

/**
 * Class Transaction
 * @package Bitcoin
 */
class Transaction implements TransactionInterface, SerializableInterface
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
    public function __construct(NetworkInterface $network = null)
    {
        $this->setNetwork($network);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getNetwork()
    {
        return $this->network;
    }

    /**
     * Set a network to a transaction
     *
     * @param NetworkInterface $network
     */
    public function setNetwork(NetworkInterface $network = null)
    {
        $this->network = $network;
    }

    /**
     * @inheritdoc
     */
    public function getTransactionId()
    {
        $hex  = $this->serialize();
        $hash = Hash::sha256d($hex);

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
     * Return a reference to the array containing the inputs
     *
     * @return array
     */
    public function &getInputsReference()
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
    public function addOutput(TransactionOutput $output)
    {
        if (!empty($output)) {
            $this->outputs[] = $output;
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
     * Return a reference to the internal outputs
     *
     * @return array
     */
    public function &getOutputsReference()
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

    public function sign(PrivateKeyInterface $privateKey, TransactionOutputInterface $txOut, $inputToSign, KInterface $kProvider = null)
    {
        if (is_null($kProvider)) {
            $kProvider = new \Bitcoin\Signature\K\RandomK();
        }

        $hash = (new SignatureHash($this))
            ->calculateHash($txOut, $inputToSign);

        $sig = $privateKey->sign($hash, $kProvider);


        return $sig;
    }

    /**
     * @param Parser $parser
     * @throws \Bitcoin\Exceptions\ParserOutOfRange
     * @throws \Exception
     */
    public function fromParser(Parser &$parser)
    {
        $this->setVersion($parser->readBytes(4, true)->serialize('int'));

        $inputC = $parser->getVarInt()->serialize('int');

        for ($i = 0; $i < $inputC; $i++) {
            $input = new TransactionInput();
            $this->addInput(
                $input->fromParser($parser)
            );
        }

        $outputC = $parser->getVarInt()->serialize('int');
        for ($i = 0; $i < $outputC; $i++) {
            $output = new TransactionOutput();
            $this->addOutput(
                $output->fromParser($parser)
            );
        }

        $this->setLockTime($parser->readBytes(4, true)->serialize('int'));
    }

    /**
     * Take a $hex string, and return an instance of a Transaction
     *
     * @param $hex
     * @return Transaction
     */
    public static function fromHex($hex, NetworkInterface $network = null)
    {
        $parser = new Parser($hex);
        $transaction = new self();
        $transaction->fromParser($parser);
        return $transaction;
    }

    /**
     * Serialize this object to a binary string ($type = null), hex string ($type = 'hex'),
     * int (although this isn't meaningful), or a bitcoind style array.
     *
     * @param $type
     * @return string
     */
    public function serialize($type = null)
    {
        if ($type == 'array') {
            return $this->toArray();
        }

        $parser = new Parser();
        $parser->writeInt(4, $this->getVersion(), true)
            ->writeArray($this->getInputs())
            ->writeArray($this->getOutputs())
            ->writeInt(4, $this->getLockTime(), true);

        return $parser
            ->getBuffer()
            ->serialize($type);
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
                'scriptSig' => array(
                    'hex' => $input->getScript()->serialize('hex'),
                    'asm' => $input->getScript()->getAsm()
                )
            );
        }, $this->getInputs());

        $outputs = array_map(function (TransactionOutputInterface $output) {
            return array(
                'value' => $output->getValue(),
                'scriptPubKey' => array(
                    'hex' => $output->getScript()->serialize('hex'),
                    'asm' => $output->getScript()->getAsm()
                )
            );
        }, $this->getOutputs());

        return array(
            'txid' => $this->getTransactionId(),
            'version' => $this->getVersion(),
            'locktime' => $this->getLockTime(),
            'vin' => $inputs,
            'vout' => $outputs
        );
    }

    public function __toString()
    {
        return $this->serialize('hex');
    }

    public function getSize($type = null)
    {
        return strlen($this->serialize($type));
    }
}
