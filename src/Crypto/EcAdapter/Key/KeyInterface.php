<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Key;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\SerializableInterface;

interface KeyInterface extends SerializableInterface
{
    /**
     * Check if the key should be be using compressed format
     *
     * @return bool
     */
    public function isCompressed();

    /**
     * Return a boolean indicating whether the key is private.
     *
     * @return bool
     */
    public function isPrivate();

    /**
     * Return the hash of the public key.
     *
     * @return Buffer
     */
    public function getPubKeyHash();

    /**
     * @return \BitWasp\Bitcoin\Address\PayToPubKeyHashAddress
     */
    public function getAddress();

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

    /**
     * @return \BitWasp\Buffertools\Buffer
     */
    public function getBuffer();
}
