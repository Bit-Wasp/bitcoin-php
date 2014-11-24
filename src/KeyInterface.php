<?php

namespace Bitcoin;

/**
 * Interface KeyInterface
 * @package Bitcoin
 */
interface KeyInterface
{
    /**
     * Check if the key should be be using compressed format
     * @return mixed
     */
    public function isCompressed();

    /**
     * Return the hash of the public key.
     * @return mixed
     */
    public function getPubKeyHash();
}
