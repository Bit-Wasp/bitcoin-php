<?php

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\SerializableInterface;

interface TransactionSignatureInterface extends SerializableInterface
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
