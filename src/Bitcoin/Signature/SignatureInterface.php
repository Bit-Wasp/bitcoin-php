<?php

namespace Afk11\Bitcoin\Signature;

use Afk11\Bitcoin\SerializableInterface;

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
