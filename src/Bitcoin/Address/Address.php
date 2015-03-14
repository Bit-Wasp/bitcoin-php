<?php

namespace Afk11\Bitcoin\Address;

use Afk11\Bitcoin\Base58;
use Afk11\Bitcoin\Network\NetworkInterface;

abstract class Address implements AddressInterface
{
    public function getAddress(NetworkInterface $network)
    {
        return Base58::encodeCheck($this->getPrefixByte($network) . $this->getHash());
    }
}
