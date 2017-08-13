<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputData;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Buffertools\BufferInterface;

interface InputSignerInterface
{
    /**
     * @return InputSigner
     */
    public function extract();

    /**
     * Calculates the signature hash for the input for the given $sigHashType.
     *
     * @param int $sigHashType
     * @return BufferInterface
     */
    public function getSigHash($sigHashType);

    /**
     * Returns whether all required signatures have been provided.
     *
     * @return bool
     */
    public function isFullySigned();

    /**
     * Returns the required number of signatures for this input.
     *
     * @return int
     */
    public function getRequiredSigs();

    /**
     * Returns an array where the values are either null,
     * or a TransactionSignatureInterface.
     *
     * @return TransactionSignatureInterface[]
     */
    public function getSignatures();

    /**
     * Returns an array where the values are either null,
     * or a PublicKeyInterface.
     *
     * @return PublicKeyInterface[]
     */
    public function getPublicKeys();

    /**
     * OutputData for the script to be signed (will be
     * equal to getScriptPubKey, or getRedeemScript, or
     * getWitnessScript.
     *
     * @return OutputData
     */
    public function getSignScript();

    /**
     * OutputData for the txOut script.
     *
     * @return OutputData
     */
    public function getScriptPubKey();

    /**
     * Returns OutputData for the P2SH redeemScript.
     *
     * @return OutputData
     */
    public function getRedeemScript();

    /**
     * Returns OutputData for the P2WSH witnessScript.
     *
     * @return OutputData
     */
    public function getWitnessScript();

    /**
     * Returns whether the scriptPubKey is P2SH.
     *
     * @return bool
     */
    public function isP2SH();

    /**
     * Returns whether the scriptPubKey or redeemScript is P2WSH.
     *
     * @return bool
     */
    public function isP2WSH();

    /**
     * Sign the input using $key and $sigHashTypes
     *
     * @param PrivateKeyInterface $privateKey
     * @param int $sigHashType
     * @return $this
     */
    public function sign(PrivateKeyInterface $privateKey, $sigHashType = SigHash::ALL);

    /**
     * Verifies the input using $flags for script verification, otherwise
     * uses the default, or that passed from SignData.
     *
     * @param int $flags
     * @return bool
     */
    public function verify($flags = null);

    /**
     * Produces a SigValues instance containing the scriptSig & script witness
     *
     * @return SigValues
     */
    public function serializeSignatures();
}
