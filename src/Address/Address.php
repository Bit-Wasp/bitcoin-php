<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\NetworkInterface;
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
    private $hash;

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

    /**
     * @param NetworkInterface|null $network
     * @return string
     */
    public function getAddress(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        $payload = new Buffer($this->getPrefixByte($network) . $this->getHash()->getBinary());
        return Base58::encodeCheck($payload);
    }
}
