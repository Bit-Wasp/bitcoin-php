<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Network\NetworkInterface;

abstract class Address implements AddressInterface
{
    /**
     * @var string
     */
    private $hash;

    public function __construct($hash)
    {
        $this->hash = (string)$hash;
    }

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
