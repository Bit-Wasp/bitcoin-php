<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Locktime;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Transaction\Bip69\Bip69;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\OutPointInterface;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

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
     * @var array
     */
    private $witness;

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
        $this->witness = [];
        $this->nLockTime = 0;
        return $this;
    }

    /**
     * @return TransactionInterface
     */
    private function makeTransaction(): TransactionInterface
    {
        return new Transaction($this->nVersion, $this->inputs, $this->outputs, $this->witness, $this->nLockTime);
    }

    /**
     * @return TransactionInterface
     */
    public function get(): TransactionInterface
    {
        return $this->makeTransaction();
    }

    /**
     * @return TransactionInterface
     */
    public function getAndReset(): TransactionInterface
    {
        $transaction = $this->makeTransaction();
        $this->reset();
        return $transaction;
    }

    /**
     * @param int $nVersion
     * @return $this
     */
    public function version(int $nVersion)
    {
        $this->nVersion = $nVersion;
        return $this;
    }

    /**
     * @param BufferInterface|string $hashPrevOut - hex or BufferInterface
     * @param int $nPrevOut
     * @param ScriptInterface $script
     * @param int $nSequence
     * @return $this
     */
    public function input($hashPrevOut, int $nPrevOut, ScriptInterface $script = null, int $nSequence = TransactionInputInterface::SEQUENCE_FINAL)
    {
        if ($hashPrevOut instanceof BufferInterface) {
            if ($hashPrevOut->getSize() !== 32) {
                throw new \InvalidArgumentException("Invalid size for txid buffer");
            }
        } else if (is_string($hashPrevOut)) {
            $hashPrevOut = Buffer::hex($hashPrevOut, 32);
        } else {
            throw new \InvalidArgumentException("Invalid value for hashPrevOut in TxBuilder::input");
        }

        $this->inputs[] = new TransactionInput(
            new OutPoint($hashPrevOut, $nPrevOut),
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
     * @param integer $value
     * @param ScriptInterface $script
     * @return $this
     */
    public function output(int $value, ScriptInterface $script)
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
     * @param ScriptWitnessInterface[] $witness
     * @return $this
     */
    public function witnesses(array $witness)
    {
        array_walk($witness, function (ScriptWitnessInterface $witness) {
            $this->witness[] = $witness;
        });

        return $this;
    }

    /**
     * @param int $locktime
     * @return $this
     */
    public function locktime(int $locktime)
    {
        $this->nLockTime = $locktime;
        return $this;
    }

    /**
     * @param Locktime $lockTime
     * @param int $nTimestamp
     * @return $this
     * @throws \Exception
     */
    public function lockToTimestamp(Locktime $lockTime, int $nTimestamp)
    {
        $this->locktime($lockTime->fromTimestamp($nTimestamp));
        return $this;
    }

    /**
     * @param Locktime $lockTime
     * @param int $blockHeight
     * @return $this
     * @throws \Exception
     */
    public function lockToBlockHeight(Locktime $lockTime, int $blockHeight)
    {
        $this->locktime($lockTime->fromBlockHeight($blockHeight));
        return $this;
    }

    /**
     * @param OutPointInterface $outpoint
     * @param ScriptInterface|null $script
     * @param int $nSequence
     * @return $this
     */
    public function spendOutPoint(OutPointInterface $outpoint, ScriptInterface $script = null, int $nSequence = TransactionInputInterface::SEQUENCE_FINAL)
    {
        $this->inputs[] = new TransactionInput(
            $outpoint,
            $script ?: new Script(),
            $nSequence
        );

        return $this;
    }

    /**
     * @param TransactionInterface $transaction
     * @param int $outputToSpend
     * @param ScriptInterface|null $script
     * @param int $nSequence
     * @return $this
     */
    public function spendOutputFrom(TransactionInterface $transaction, int $outputToSpend, ScriptInterface $script = null, int $nSequence = TransactionInputInterface::SEQUENCE_FINAL)
    {
        // Check TransactionOutput exists in $tx
        $transaction->getOutput($outputToSpend);
        $this->input(
            $transaction->getTxId(),
            $outputToSpend,
            $script,
            $nSequence
        );

        return $this;
    }

    /**
     * Create an output paying $value to an Address.
     *
     * @param int $value
     * @param AddressInterface $address
     * @return $this
     */
    public function payToAddress(int $value, AddressInterface $address)
    {
        // Create Script from address, then create an output.
        $this->output(
            $value,
            $address->getScriptPubKey()
        );

        return $this;
    }

    /**
     * Sorts the transaction inputs and outputs lexicographically,
     * according to BIP69
     *
     * @param Bip69 $bip69
     * @return $this
     */
    public function bip69(Bip69 $bip69)
    {
        list ($inputs, $witness) = $bip69->sortInputsAndWitness($this->inputs, $this->witness);

        $this->inputs = $inputs;
        $this->outputs = $bip69->sortOutputs($this->outputs);
        $this->witness = $witness;

        return $this;
    }
}
