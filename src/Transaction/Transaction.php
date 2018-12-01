<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Util\IntRange;
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
     * @var BufferInterface
     */
    private $wtxid;

    /**
     * @var BufferInterface
     */
    private $hash;

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
        int $nVersion = TransactionInterface::DEFAULT_VERSION,
        array $vin = [],
        array $vout = [],
        array $vwit = [],
        int $nLockTime = 0
    ) {
        if ($nVersion < IntRange::I32_MIN || $nVersion > IntRange::I32_MAX) {
            throw new \InvalidArgumentException('Transaction version is outside valid range');
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
    public function getTxHash(): BufferInterface
    {
        if (null === $this->hash) {
            $this->hash = Hash::sha256d($this->getBaseSerialization());
        }
        return $this->hash;
    }

    /**
     * @return BufferInterface
     */
    public function getTxId(): BufferInterface
    {
        return $this->getTxHash()->flip();
    }

    /**
     * @return BufferInterface
     */
    public function getWitnessTxId(): BufferInterface
    {
        if (null === $this->wtxid) {
            $this->wtxid = Hash::sha256d($this->getBuffer())->flip();
        }

        return $this->wtxid;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Get the array of inputs in the transaction
     *
     * @return TransactionInputInterface[]
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * @param int $index
     * @return TransactionInputInterface
     */
    public function getInput(int $index): TransactionInputInterface
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
    public function getOutputs(): array
    {
        return $this->outputs;
    }

    /**
     * @param int $vout
     * @return TransactionOutputInterface
     */
    public function getOutput(int $vout): TransactionOutputInterface
    {
        if (!isset($this->outputs[$vout])) {
            throw new \RuntimeException('No output at this index');
        }
        return $this->outputs[$vout];
    }

    /**
     * @return bool
     */
    public function hasWitness(): bool
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
    public function getWitnesses(): array
    {
        return $this->witness;
    }

    /**
     * @param int $index
     * @return ScriptWitnessInterface
     */
    public function getWitness(int $index): ScriptWitnessInterface
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
    public function makeOutpoint(int $vout): OutPointInterface
    {
        $this->getOutput($vout);
        return new OutPoint($this->getTxId(), $vout);
    }

    /**
     * @param int $vout
     * @return Utxo
     */
    public function makeUtxo(int $vout): Utxo
    {
        return new Utxo(new OutPoint($this->getTxId(), $vout), $this->getOutput($vout));
    }

    /**
     * Get Lock Time
     *
     * @return int
     */
    public function getLockTime(): int
    {
        return $this->lockTime;
    }

    /**
     * @return int
     */
    public function getValueOut(): int
    {
        $value = 0;
        foreach ($this->outputs as $output) {
            $value = $value + $output->getValue();
        }

        return $value;
    }

    /**
     * @return bool
     */
    public function isCoinbase(): bool
    {
        return count($this->inputs) === 1 && $this->getInput(0)->isCoinBase();
    }

    /**
     * @param TransactionInterface $tx
     * @return bool
     */
    public function equals(TransactionInterface $tx): bool
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
    public function getBuffer(): BufferInterface
    {
        return (new TransactionSerializer())->serialize($this);
    }

    /**
     * @return BufferInterface
     */
    public function getBaseSerialization(): BufferInterface
    {
        return (new TransactionSerializer())->serialize($this, TransactionSerializer::NO_WITNESS);
    }

    /**
     * @return BufferInterface
     */
    public function getWitnessSerialization(): BufferInterface
    {
        if (!$this->hasWitness()) {
            throw new \RuntimeException('Cannot get witness serialization for transaction without witnesses');
        }

        return $this->getBuffer();
    }

    /**
     * {@inheritdoc}
     * @see TransactionInterface::getWitnessBuffer()
     * @see TransactionInterface::getWitnessSerialization()
     * @deprecated
     */
    public function getWitnessBuffer(): BufferInterface
    {
        return $this->getWitnessSerialization();
    }
}
