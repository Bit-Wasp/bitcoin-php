<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\Collection\Transaction\TransactionWitnessCollection;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\OldTransactionSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Transaction\SignatureHash\Hasher;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\CommonTrait\FunctionAliasArrayAccess;

class Transaction extends Serializable implements TransactionInterface
{
    use FunctionAliasArrayAccess;

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
     * @var TransactionWitnessCollection
     */
    private $witness;

    /**
     * @var int|string
     */
    private $lockTime;

    /**
     * Transaction constructor.
     *
     * @param int $nVersion
     * @param TransactionInputCollection|null $inputs
     * @param TransactionOutputCollection|null $outputs
     * @param TransactionWitnessCollection|null $witness
     * @param int $nLockTime
     */
    public function __construct(
        $nVersion = TransactionInterface::DEFAULT_VERSION,
        TransactionInputCollection $inputs = null,
        TransactionOutputCollection $outputs = null,
        TransactionWitnessCollection $witness = null,
        $nLockTime = 0
    ) {
        $math = Bitcoin::getMath();
        if ($math->cmp($nVersion, TransactionInterface::MAX_VERSION) > 0) {
            throw new \InvalidArgumentException('Version must be less than ' . TransactionInterface::MAX_VERSION);
        }

        if ($math->cmp($nLockTime, 0) < 0 || $math->cmp($nLockTime, TransactionInterface::MAX_LOCKTIME) > 0) {
            throw new \InvalidArgumentException('Locktime must be positive and less than ' . TransactionInterface::MAX_LOCKTIME);
        }

        $this->version = $nVersion;
        $this->inputs = $inputs ?: new TransactionInputCollection();
        $this->outputs = $outputs ?: new TransactionOutputCollection();
        $this->witness = $witness ?: new TransactionWitnessCollection();
        $this->lockTime = $nLockTime;

        $this
            ->initFunctionAlias('version', 'getVersion')
            ->initFunctionAlias('inputs', 'getInputs')
            ->initFunctionAlias('outputs', 'getOutputs')
            ->initFunctionAlias('locktime', 'getLockTime');
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
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function getWitnessTxId()
    {
        return Hash::sha256d($this->getWitnessBuffer())->flip();
    }

    /**
     * @return int|string
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
        return $this->inputs[$index];
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
     * @param int $vout
     * @return TransactionOutputInterface
     */
    public function getOutput($vout)
    {
        return $this->outputs[$vout];
    }

    /**
     * @return TransactionWitnessCollection
     */
    public function getWitnesses()
    {
        return $this->witness;
    }

    /**
     * @return ScriptWitnessInterface
     */
    public function getWitness($index)
    {
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
        $output = $this->getOutput($vout);

        return new Utxo(
            new OutPoint($this->getTxId(), $vout),
            $output
        );
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
     * @return \BitWasp\Bitcoin\Transaction\SignatureHash\SigHashInterface
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
        if ($nIn !== count($tx->getInputs()) || $nOut !== count($tx->getOutputs()) || $nWit !== count($tx->getWitnesses())) {
            return false;
        }

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
     * @return Validator
     */
    public function validator()
    {
        return new Validator($this);
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
