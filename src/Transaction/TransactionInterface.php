<?php

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
    public function isCoinbase();

    /**
     * Get the transactions sha256d hash.
     *
     * @return BufferInterface
     */
    public function getTxHash();

    /**
     * Get the little-endian sha256d hash.
     * @return BufferInterface
     */
    public function getTxId();

    /**
     * Get the little endian sha256d hash including witness data
     * @return BufferInterface
     */
    public function getWitnessTxId();

    /**
     * Get the version of this transaction
     *
     * @return int
     */
    public function getVersion();

    /**
     * Return an array of all inputs
     *
     * @return TransactionInputInterface[]
     */
    public function getInputs();

    /**
     * @param int $index
     * @return TransactionInputInterface
     */
    public function getInput($index);

    /**
     * Return an array of all outputs
     *
     * @return TransactionOutputInterface[]
     */
    public function getOutputs();

    /**
     * @param int $vout
     * @return TransactionOutputInterface
     */
    public function getOutput($vout);

    /**
     * @param int $index
     * @return ScriptWitnessInterface
     */
    public function getWitness($index);

    /**
     * @return ScriptWitnessInterface[]
     */
    public function getWitnesses();

    /**
     * @param int $vout
     * @return OutPointInterface
     */
    public function makeOutPoint($vout);

    /**
     * @param int $vout
     * @return Utxo
     */
    public function makeUtxo($vout);

    /**
     * Return the locktime for this transaction
     *
     * @return int
     */
    public function getLockTime();

    /**
     * @return int|string
     */
    public function getValueOut();

    /**
     * @return bool
     */
    public function hasWitness();

    /**
     * @param TransactionInterface $tx
     * @return bool
     */
    public function equals(TransactionInterface $tx);

    /**
     * @return BufferInterface
     */
    public function getBaseSerialization();

    /**
     * @return BufferInterface
     */
    public function getWitnessSerialization();

    /**
     * @deprecated
     * @return BufferInterface
     */
    public function getWitnessBuffer();
}
