<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Buffertools\BufferInterface;

/**
 * Abstract Class Address
 * Used to store a hash, and a base58 encoded address
 */
abstract class Address implements AddressInterface
{
    /**
     * @var BufferInterface
     */
    protected $hash;

    /**
     * @param BufferInterface $hash
     */
    public function __construct(BufferInterface $hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return BufferInterface
     */
    public function getHash()
    {
        return $this->hash;
    }
}
