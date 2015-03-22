<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Network\NetworkInterface;

abstract class Address implements AddressInterface
{
    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function getAddress(NetworkInterface $network)
    {
        return Base58::encodeCheck($this->getPrefixByte($network) . $this->getHash());
    }
}
