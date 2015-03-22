<?php

namespace BitWasp\Bitcoin\Signature;

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

    /**
     * Return the sighash type
     * @return int
     */
    public function getSigHashType();
}
