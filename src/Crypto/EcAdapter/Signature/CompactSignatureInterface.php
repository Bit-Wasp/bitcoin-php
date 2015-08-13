<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Signature;

interface CompactSignatureInterface extends SignatureInterface
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
     * @return int
     */
    public function getRecoveryId();

    /**
     * @return bool
     */
    public function isCompressed();
}
