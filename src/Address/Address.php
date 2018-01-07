<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Address;

use BitWasp\Buffertools\BufferInterface;

/**
 * Abstract Class Address
 * Used to store a hash
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
    public function getHash(): BufferInterface
    {
        return $this->hash;
    }
}
