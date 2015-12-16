<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\SerializableInterface;
use BitWasp\Bitcoin\Transaction\SignatureHash\SignatureHashInterface;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;

interface TransactionInterface extends SerializableInterface, \ArrayAccess
{
    const DEFAULT_VERSION = 1;

    /**
     * The version parameter is encoded as a uint32
     */

    const MAX_VERSION = '4294967295';

    /**
     * The locktime parameter is encoded as a uint32
     */
    const MAX_LOCKTIME = '4294967295';

    /**
     * @return bool
     */
    public function isCoinbase();

    /**
     * Get the transactions sha256d hash.
     *
     * @return Buffer
     */
    public function getTxHash();

    /**
     * Get the little-endian sha256d hash.
     * @return Buffer
     */
    public function getTxId();

    /**
     * Get the version of this transaction
     *
     * @return int|string
     */
    public function getVersion();

    /**
     * Return an array of all inputs
     *
     * @return TransactionInputCollection
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
     * @return TransactionOutputCollection
     */
    public function getOutputs();

    /**
     * @param int $vout
     * @return TransactionOutputInterface
     */
    public function getOutput($vout);

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
     * @return int|string
     */
    public function getLockTime();

    /**
     * @return int|string
     */
    public function getValueOut();

    /**
     * @return SignatureHashInterface
     */
    public function getSignatureHash();
}
