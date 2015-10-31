<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Signature;

use BitWasp\Bitcoin\SerializableInterface;

interface CompactSignatureInterface extends SerializableInterface
{
    /**
     * @return int|string
     */
    public function getR();

    /**
     * @return int|string
     */
    public function getS();

    /**
     * @return SignatureInterface
     */
    public function convert();

    /**
     * @return int
     */
    public function getRecoveryId();

    /**
     * @return bool
     */
    public function isCompressed();
}
