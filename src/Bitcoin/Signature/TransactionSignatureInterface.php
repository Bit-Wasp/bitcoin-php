<?php

namespace BitWasp\Bitcoin\Signature;

interface TransactionSignatureInterface
{
    /**
     * @return SignatureInterface
     */
    public function getSignature();

    /**
     * @return int|string
     */
    public function getHashType();
}
