<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Buffertools\Buffer;
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
     * @return \BitWasp\Buffertools\Buffer
     */
    public function getBuffer();

    /**
     * @param integer $offset
     * @return KeyInterface
     */
    public function tweakAdd($offset);

    /**
     * @param integer $offset
     * @return KeyInterface
     */
    public function tweakMul($offset);
}
