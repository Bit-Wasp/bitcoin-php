<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\SerializableInterface;

interface KeyInterface extends SerializableInterface
{
    /**
     * Check if the key should be be using compressed format
     *
     * @return mixed
     */
    public function isCompressed();

    /**
     * Return the hash of the public key.
     *
     * @return Buffer
     */
    public function getPubKeyHash();

    /**
     * Return a boolean indicating whether the key is private.
     *
     * @return bool
     */
    public function isPrivate();

    /**
     * @return \BitWasp\Bitcoin\Address\PayToPubKeyHashAddress
     */
    public function getAddress();

    /**
     * @return \BitWasp\Bitcoin\Buffer
     */
    public function getBuffer();
}
