<?php

namespace Bitcoin;

use Bitcoin\Util\Parser;
use Bitcoin\Util\Buffer;
use Bitcoin\Util\Math;

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
    public function __construct(NetworkInterface $network = null)
    {
        $this->network = $network;
        return $this;
    }

    /**
     * @param $parser
     * @throws \Exception
     */
    public function fromParser(Parser &$parser)
    {
        $this->setVersion($parser->readBytes(4, true));

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

        $this->setLockTime($parser->readBytes(4, true));
    }

    /**
     * @param $type
     * @return string
     */
    public function serialize($type)
    {
        $parser = new Parser();
        $parser->writeInt(4, $this->getVersion()->serialize('int'), true)
            ->writeArray($this->getInputs())
            ->writeArray($this->getOutputs())
            ->writeBytes(4, $this->getLockTime()->serialize('int'));

        return $parser
            ->getBuffer()
            ->serialize($type);
    }

    /**
     * @param $hex
     * @return Transaction
     */
    public static function fromHex($hex)
    {
        $parser = new Parser($hex);
        $transaction = new self();
        $transaction->fromParser($parser);
        return $transaction;
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
    public function setVersion(Buffer $version)
    {
        if (Math::cmp($version->serialize('int'), TransactionInterface::MAX_VERSION) > 0) {
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
    public function setLockTime(Buffer $locktime)
    {
        if (Math::cmp($locktime->serialize('int'), TransactionInterface::MAX_LOCKTIME) > 0) {
            throw new \Exception('Locktime must be less than ' . TransactionInterface::MAX_LOCKTIME);
        }

        $this->locktime = $locktime;
        return $this;
    }
}
