<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Transaction\SignatureHash\Hasher;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;
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
     * @var int|string
     */
    private $lockTime;

    /**
     * @param int|string $nVersion
     * @param TransactionInputCollection $inputs
     * @param TransactionOutputCollection $outputs
     * @param int|string $nLockTime
     * @throws \Exception
     */
    public function __construct(
        $nVersion = TransactionInterface::DEFAULT_VERSION,
        TransactionInputCollection $inputs = null,
        TransactionOutputCollection $outputs = null,
        $nLockTime = '0'
    ) {

        if (!is_numeric($nVersion)) {
            throw new \InvalidArgumentException('Transaction version must be numeric');
        }

        if (!is_numeric($nLockTime)) {
            throw new \InvalidArgumentException('Transaction locktime must be numeric');
        }

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
     * @return \BitWasp\Bitcoin\Transaction\SignatureHash\SignatureHashInterface
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
     * @return Validator
     */
    public function validator()
    {
        return new Validator($this);
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new TransactionSerializer)->serialize($this);
    }
}
