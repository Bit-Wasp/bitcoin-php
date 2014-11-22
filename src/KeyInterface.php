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
     * Return the hex / buffer of this key.
     * @return mixed
     */
    public function getHex();
} 