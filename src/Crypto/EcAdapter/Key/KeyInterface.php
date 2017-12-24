<?php

declare(strict_types=1);

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
    public function isCompressed(): bool;

    /**
     * Return a boolean indicating whether the key is private.
     *
     * @return bool
     */
    public function isPrivate(): bool;

    /**
     * Return the hash of the public key.
     *
     * @param PublicKeySerializerInterface|null $serializer
     * @return BufferInterface
     */
    public function getPubKeyHash(PublicKeySerializerInterface $serializer = null): BufferInterface;

    /**
     * @param \GMP $offset
     * @return KeyInterface
     */
    public function tweakAdd(\GMP $offset): KeyInterface;

    /**
     * @param \GMP $offset
     * @return KeyInterface
     */
    public function tweakMul(\GMP $offset): KeyInterface;

    /**
     * @return BufferInterface
     */
    public function getBuffer(): BufferInterface;
}
