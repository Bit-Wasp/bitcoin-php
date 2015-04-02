<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Network\NetworkInterface;

/**
 * Abstract Class Address
 * Used to store a hash, and a base58 encoded address
 */
abstract class Address implements AddressInterface
{
    /**
     * @var string
     */
    private $hash;

    /**
     * @param Buffer $hash
     */
    public function __construct(Buffer $hash)
    {
        $this->hash = (string)$hash;
    }

    /**
     * @return Buffer
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function getAddress(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        $payload = Buffer::hex($this->getPrefixByte($network) . $this->getHash());
        return Base58::encodeCheck($payload);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getAddress();
    }
}
