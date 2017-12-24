<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Mutator;

use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class TxMutator
{
    /**
     * @var TransactionInterface
     */
    private $transaction;

    /**
     * @var InputCollectionMutator
     */
    private $inputsMutator;

    /**
     * @var OutputCollectionMutator
     */
    private $outputsMutator;

    /**
     * @param TransactionInterface $transaction
     */
    public function __construct(TransactionInterface $transaction)
    {
        $this->transaction = clone $transaction;
    }

    /**
     * @return InputCollectionMutator
     */
    public function inputsMutator(): InputCollectionMutator
    {
        if (null === $this->inputsMutator) {
            $this->inputsMutator = new InputCollectionMutator($this->transaction->getInputs());
        }

        return $this->inputsMutator;
    }

    /**
     * @return OutputCollectionMutator
     */
    public function outputsMutator(): OutputCollectionMutator
    {
        if (null === $this->outputsMutator) {
            $this->outputsMutator = new OutputCollectionMutator($this->transaction->getOutputs());
        }

        return $this->outputsMutator;
    }

    /**
     * @return TransactionInterface
     */
    public function done(): TransactionInterface
    {
        if (null !== $this->inputsMutator) {
            $this->inputs($this->inputsMutator->done());
        }

        if (null !== $this->outputsMutator) {
            $this->outputs($this->outputsMutator->done());
        }

        return $this->transaction;
    }

    /**
     * @param array $array
     * @return $this
     */
    private function replace(array $array = [])
    {
        $this->transaction = new Transaction(
            array_key_exists('version', $array) ? $array['version'] : $this->transaction->getVersion(),
            array_key_exists('inputs', $array) ? $array['inputs'] : $this->transaction->getInputs(),
            array_key_exists('outputs', $array) ? $array['outputs'] : $this->transaction->getOutputs(),
            array_key_exists('witness', $array) ? $array['witness'] : $this->transaction->getWitnesses(),
            array_key_exists('nLockTime', $array) ? $array['nLockTime'] : $this->transaction->getLockTime()
        );

        return $this;
    }

    /**
     * @param int $nVersion
     * @return $this
     */
    public function version(int $nVersion)
    {
        return $this->replace(array('version' => $nVersion));
    }

    /**
     * @param TransactionInputInterface[] $inputCollection
     * @return $this
     */
    public function inputs(array $inputCollection)
    {
        return $this->replace(array('inputs' => $inputCollection));
    }

    /**
     * @param TransactionOutputInterface[] $outputCollection
     * @return $this
     */
    public function outputs(array $outputCollection)
    {
        return $this->replace(array('outputs' => $outputCollection));
    }

    /**
     * @param ScriptWitnessInterface[] $witnessCollection
     * @return $this
     */
    public function witness(array $witnessCollection)
    {
        return $this->replace(array('witness' => $witnessCollection));
    }

    /**
     * @param int $locktime
     * @return $this
     */
    public function locktime(int $locktime)
    {
        return $this->replace(array('nLockTime' => $locktime));
    }
}
