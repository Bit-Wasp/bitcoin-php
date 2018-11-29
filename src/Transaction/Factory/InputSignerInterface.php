<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\FullyQualifiedScript;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Buffertools\BufferInterface;

interface InputSignerInterface
{
    /**
     * Calculates the signature hash for the input for the given $sigHashType.
     *
     * @param int $sigHashType
     * @return BufferInterface
     */
    public function getSigHash(int $sigHashType): BufferInterface;

    /**
     * Returns whether all required signatures have been provided.
     *
     * @return bool
     */
    public function isFullySigned(): bool;

    /**
     * Returns the required number of signatures for this input.
     *
     * @return int
     */
    public function getRequiredSigs(): int;

    /**
     * Returns an array where the values are either null,
     * or a TransactionSignatureInterface.
     *
     * @return TransactionSignatureInterface[]
     */
    public function getSignatures(): array;

    /**
     * Returns an array where the values are either null,
     * or a PublicKeyInterface.
     *
     * @return PublicKeyInterface[]
     */
    public function getPublicKeys(): array;

    /**
     * OutputData for the txOut script.
     *
     * @return FullyQualifiedScript
     */
    public function getInputScripts(): FullyQualifiedScript;

    /**
     * @return mixed
     */
    public function getSteps();

    /**
     * @param int $idx
     * @return Checksig[]|Conditional[]
     */
    public function step(int $idx);

    /**
     * @param int $idx
     * @param PrivateKeyInterface $privateKey
     * @param int $sigHashType
     * @return mixed
     */
    public function signStep(int $idx, PrivateKeyInterface $privateKey, int $sigHashType = SigHash::ALL);

    /**
     * Sign the input using $key and $sigHashTypes
     *
     * @param PrivateKeyInterface $privateKey
     * @param int $sigHashType
     * @return $this
     */
    public function sign(PrivateKeyInterface $privateKey, int $sigHashType = SigHash::ALL);

    /**
     * Verifies the input using $flags for script verification, otherwise
     * uses the default, or that passed from SignData.
     *
     * @param int $flags
     * @return bool
     */
    public function verify(int $flags = null): bool;

    /**
     * Produces a SigValues instance containing the scriptSig & script witness
     *
     * @return SigValues
     */
    public function serializeSignatures(): SigValues;
}
