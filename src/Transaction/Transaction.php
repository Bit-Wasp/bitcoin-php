<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\OldTransactionSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\BufferInterface;

class Transaction extends Serializable implements TransactionInterface
{
    /**
     * @var int
     */
    private $version;

    /**
     * @var TransactionInputInterface[]
     */
    private $inputs;

    /**
     * @var TransactionOutputInterface[]
     */
    private $outputs;

    /**
     * @var ScriptWitnessInterface[]
     */
    private $witness;

    /**
     * @var int
     */
    private $lockTime;

    /**
     * Transaction constructor.
     *
     * @param int $nVersion
     * @param TransactionInputInterface[] $vin
     * @param TransactionOutputInterface[] $vout
     * @param ScriptWitnessInterface[] $vwit
     * @param int $nLockTime
     */
    public function __construct(
        $nVersion = TransactionInterface::DEFAULT_VERSION,
        array $vin = [],
        array $vout = [],
        array $vwit = [],
        $nLockTime = 0
    ) {
        if ($nVersion > TransactionInterface::MAX_VERSION) {
            throw new \InvalidArgumentException('Version must be less than ' . TransactionInterface::MAX_VERSION);
        }

        if ($nLockTime < 0 || $nLockTime > TransactionInterface::MAX_LOCKTIME) {
            throw new \InvalidArgumentException('Locktime must be positive and less than ' . TransactionInterface::MAX_LOCKTIME);
        }

        $this->version = $nVersion;
        $this->lockTime = $nLockTime;

        $this->inputs = array_map(function (TransactionInputInterface $input) {
            return $input;
        }, $vin);
        $this->outputs = array_map(function (TransactionOutputInterface $output) {
            return $output;
        }, $vout);
        $this->witness = array_map(function (ScriptWitnessInterface $scriptWitness) {
            return $scriptWitness;
        }, $vwit);
    }

    /**
     * @return BufferInterface
     */
    public function getTxHash()
    {
        return Hash::sha256d($this->getBuffer());
    }

    /**
     * @return BufferInterface
     */
    public function getTxId()
    {
        return $this->getTxHash()->flip();
    }

    /**
     * @return BufferInterface
     */
    public function getWitnessTxId()
    {
        return Hash::sha256d($this->getWitnessBuffer())->flip();
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Get the array of inputs in the transaction
     *
     * @return TransactionInputInterface[]
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
        if (!isset($this->inputs[$index])) {
            throw new \RuntimeException('No input at this index');
        }
        return $this->inputs[$index];
    }

    /**
     * Get Outputs
     *
     * @return TransactionOutputInterface[]
     */
    public function getOutputs()
    {
        return $this->outputs;
    }

    /**
     * @param int $vout
     * @return TransactionOutputInterface
     */
    public function getOutput($vout)
    {
        if (!isset($this->outputs[$vout])) {
            throw new \RuntimeException('No output at this index');
        }
        return $this->outputs[$vout];
    }

    /**
     * @return bool
     */
    public function hasWitness()
    {
        for ($l = count($this->inputs), $i = 0; $i < $l; $i++) {
            if (isset($this->witness[$i]) && count($this->witness[$i]) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ScriptWitnessInterface[]
     */
    public function getWitnesses()
    {
        return $this->witness;
    }

    /**
     * @param int $index
     * @return ScriptWitnessInterface
     */
    public function getWitness($index)
    {
        if (!isset($this->witness[$index])) {
            throw new \RuntimeException('No witness at this index');
        }
        return $this->witness[$index];
    }

    /**
     * @param int $vout
     * @return OutPointInterface
     */
    public function makeOutpoint($vout)
    {
        $this->getOutput($vout);
        return new OutPoint($this->getTxId(), $vout);
    }

    /**
     * @param int $vout
     * @return Utxo
     */
    public function makeUtxo($vout)
    {
        return new Utxo(new OutPoint($this->getTxId(), $vout), $this->getOutput($vout));
    }

    /**
     * Get Lock Time
     *
     * @return int
     */
    public function getLockTime()
    {
        return $this->lockTime;
    }

    /**
     * @return int|string
     */
    public function getValueOut()
    {
        $math = Bitcoin::getMath();
        $value = gmp_init(0);
        foreach ($this->outputs as $output) {
            $value = $math->add($value, gmp_init($output->getValue()));
        }

        return gmp_strval($value, 10);
    }

    /**
     * @return bool
     */
    public function isCoinbase()
    {
        return count($this->inputs) === 1 && $this->getInput(0)->isCoinBase();
    }

    /**
     * @param TransactionInterface $tx
     * @return bool
     */
    public function equals(TransactionInterface $tx)
    {
        $version = gmp_cmp($this->version, $tx->getVersion());
        if ($version !== 0) {
            return false;
        }

        $nIn = count($this->inputs);
        $nOut = count($this->outputs);
        $nWit = count($this->witness);

        // Check the length of each field is equal
        if ($nIn !== count($tx->getInputs()) || $nOut !== count($tx->getOutputs()) || $nWit !== count($tx->getWitnesses())) {
            return false;
        }

        // Check each field
        for ($i = 0; $i < $nIn; $i++) {
            if (false === $this->getInput($i)->equals($tx->getInput($i))) {
                return false;
            }
        }

        for ($i = 0; $i < $nOut; $i++) {
            if (false === $this->getOutput($i)->equals($tx->getOutput($i))) {
                return false;
            }
        }

        for ($i = 0; $i < $nWit; $i++) {
            if (false === $this->getWitness($i)->equals($tx->getWitness($i))) {
                return false;
            }
        }

        return gmp_cmp($this->lockTime, $tx->getLockTime()) === 0;
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer()
    {
        return (new OldTransactionSerializer())->serialize($this);
    }

    /**
     * @return BufferInterface
     */
    public function getWitnessBuffer()
    {
        return (new TransactionSerializer())->serialize($this);
    }
}
