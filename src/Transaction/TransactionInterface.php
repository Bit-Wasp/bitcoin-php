<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\SerializableInterface;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\BufferInterface;

interface TransactionInterface extends SerializableInterface
{
    const DEFAULT_VERSION = 1;

    /**
     * The locktime parameter is encoded as a uint32
     */
    const MAX_LOCKTIME = 4294967295;

    /**
     * @return bool
     */
    public function isCoinbase(): bool;

    /**
     * Get the transactions sha256d hash.
     *
     * @return BufferInterface
     */
    public function getTxHash(): BufferInterface;

    /**
     * Get the little-endian sha256d hash.
     * @return BufferInterface
     */
    public function getTxId(): BufferInterface;

    /**
     * Get the little endian sha256d hash including witness data
     * @return BufferInterface
     */
    public function getWitnessTxId(): BufferInterface;

    /**
     * Get the version of this transaction
     *
     * @return int
     */
    public function getVersion(): int;

    /**
     * Return an array of all inputs
     *
     * @return TransactionInputInterface[]
     */
    public function getInputs(): array;

    /**
     * @param int $index
     * @return TransactionInputInterface
     */
    public function getInput(int $index): TransactionInputInterface;

    /**
     * Return an array of all outputs
     *
     * @return TransactionOutputInterface[]
     */
    public function getOutputs(): array;

    /**
     * @param int $vout
     * @return TransactionOutputInterface
     */
    public function getOutput(int $vout): TransactionOutputInterface;

    /**
     * @param int $index
     * @return ScriptWitnessInterface
     */
    public function getWitness(int $index): ScriptWitnessInterface;

    /**
     * @return ScriptWitnessInterface[]
     */
    public function getWitnesses(): array;

    /**
     * @param int $vout
     * @return OutPointInterface
     */
    public function makeOutPoint(int $vout): OutPointInterface;

    /**
     * @param int $vout
     * @return Utxo
     */
    public function makeUtxo(int $vout): Utxo;

    /**
     * Return the locktime for this transaction
     *
     * @return int
     */
    public function getLockTime(): int;

    /**
     * @return int
     */
    public function getValueOut();

    /**
     * @return bool
     */
    public function hasWitness(): bool;

    /**
     * @param TransactionInterface $tx
     * @return bool
     */
    public function equals(TransactionInterface $tx): bool;

    /**
     * @return BufferInterface
     */
    public function getBaseSerialization(): BufferInterface;

    /**
     * @return BufferInterface
     */
    public function getWitnessSerialization(): BufferInterface;

    /**
     * @deprecated
     * @return BufferInterface
     */
    public function getWitnessBuffer(): BufferInterface;
}
