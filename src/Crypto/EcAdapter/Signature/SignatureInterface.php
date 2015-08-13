<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Signature;

use BitWasp\Bitcoin\SerializableInterface;

interface SignatureInterface extends SerializableInterface
{
    /**
     * Return the R value
     *
     * @return int
     */
    public function getR();

    /**
     * Return the S value
     *
     * @return int
     */
    public function getS();
}
