<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\SerializableInterface;
use BitWasp\Buffertools\BufferInterface;

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
     * @param PublicKeySerializerInterface|null $serializer
     * @return BufferInterface
     */
    public function getPubKeyHash(PublicKeySerializerInterface $serializer = null);

    /**
     * @param \GMP $offset
     * @return KeyInterface
     */
    public function tweakAdd(\GMP $offset);

    /**
     * @param \GMP $offset
     * @return KeyInterface
     */
    public function tweakMul(\GMP $offset);

    /**
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function getBuffer();
}
