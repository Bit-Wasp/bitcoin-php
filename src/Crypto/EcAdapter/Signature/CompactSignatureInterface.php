<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Signature;

interface CompactSignatureInterface extends SignatureInterface
{
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
