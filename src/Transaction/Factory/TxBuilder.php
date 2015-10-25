<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\Locktime;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class TxBuilder
{
    /**
     * @var int
     */
    private $nVersion;

    /**
     * @var array
     */
    private $inputs;

    /**
     * @var array
     */
    private $outputs;

    /**
     * @var int
     */
    private $nLockTime;

    public function __construct()
    {
        $this->reset();
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->nVersion = 1;
        $this->inputs = [];
        $this->outputs = [];
        $this->nLockTime = 0;
        return $this;
    }

    /**
     * @return TransactionInterface
     */
    private function makeTransaction()
    {
        return new Transaction(
            $this->nVersion,
            new TransactionInputCollection($this->inputs),
            new TransactionOutputCollection($this->outputs),
            $this->nLockTime
        );
    }

    /**
     * @return TransactionInterface
     */
    public function get()
    {
        return $this->makeTransaction();
    }

    /**
     * @return TransactionInterface
     */
    public function getAndReset()
    {
        $transaction = $this->makeTransaction();
        $this->reset();
        return $transaction;
    }

    /**
     * @param int $nVersion
     * @return $this
     */
    public function version($nVersion)
    {
        $this->nVersion = $nVersion;
        return $this;
    }

    /**
     * @param string $hashPrevOut
     * @param int $nPrevOut
     * @param Script|null $script
     * @param int $nSequence
     * @return $this
     */
    public function input($hashPrevOut, $nPrevOut, Script $script = null, $nSequence = TransactionInputInterface::DEFAULT_SEQUENCE)
    {
        $this->inputs[] = new TransactionInput(
            $hashPrevOut,
            $nPrevOut,
            $script ?: new Script(),
            $nSequence
        );
        return $this;
    }

    /**
     * @param TransactionInputInterface[] $inputs
     * @return $this
     */
    public function inputs(array $inputs)
    {
        array_walk($inputs, function (TransactionInputInterface $input) {
            $this->inputs[] = $input;
        });

        return $this;
    }

    /**
     * @param int|string $value
     * @param ScriptInterface $script
     * @return $this
     */
    public function output($value, ScriptInterface $script)
    {
        $this->outputs[] = new TransactionOutput($value, $script);
        return $this;
    }

    /**
     * @param TransactionOutputInterface[] $outputs
     * @return $this
     */
    public function outputs(array $outputs)
    {
        array_walk($outputs, function (TransactionOutputInterface $output) {
            $this->outputs[] = $output;
        });

        return $this;
    }

    /**
     * @param int $locktime
     * @return $this
     */
    public function locktime($locktime)
    {
        $this->nLockTime = $locktime;
        return $this;
    }

    /**
     * @param Locktime $locktime
     * @param int $nTimestamp
     * @return $this
     * @throws \Exception
     */
    public function lockToTimestamp(Locktime $locktime, $nTimestamp)
    {
        $this->locktime($locktime->fromTimestamp($nTimestamp));
        return $this;
    }

    /**
     * @param Locktime $locktime
     * @param int $blockHeight
     * @return $this
     * @throws \Exception
     */
    public function lockToBlockHeight(Locktime $locktime, $blockHeight)
    {
        $this->locktime($locktime->fromBlockHeight($blockHeight));
        return $this;
    }

    /**
     * @param TransactionInterface $transaction
     * @param int $outputToSpend
     * @return $this
     */
    public function spendOutputFrom(TransactionInterface $transaction, $outputToSpend)
    {
        // Check TransactionOutput exists in $tx
        $transaction->getOutput($outputToSpend);
        $this->input(
            $transaction->getTxId()->getHex(),
            $outputToSpend
        );

        return $this;
    }

    /**
     * Create an output paying $value to an Address.
     *
     * @param AddressInterface $address
     * @param int $value
     * @return $this
     */
    public function payToAddress(AddressInterface $address, $value)
    {
        // Create Script from address, then create an output.
        $this->output(
            $value,
            ScriptFactory::scriptPubKey()->payToAddress($address)
        );

        return $this;
    }
}
