<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Bitcoin;
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
     * @param $hash
     */
    public function __construct($hash)
    {
        $this->hash = (string)$hash;
    }

    /**
     * @return string
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

        return Base58::encodeCheck($this->getPrefixByte($network) . $this->getHash());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getAddress();
    }
}
