<?php

namespace Bitcoin\Signature;

/**
 * Interface SignatureInterface
 * @package Bitcoin\Signature
 * @author Thomas Kerin
 */
interface SignatureInterface
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
