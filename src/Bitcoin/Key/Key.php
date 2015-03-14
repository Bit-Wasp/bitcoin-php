<?php

namespace Afk11\Bitcoin\Key;

use Afk11\Bitcoin\Address\AddressFactory;
use Afk11\Bitcoin\NetworkInterface;

abstract class Key implements KeyInterface
{
    /**
     * @param NetworkInterface $network
     * @return \Afk11\Bitcoin\Address\ScriptHashAddress
     */
    public function getAddress(NetworkInterface $network)
    {
        $address = AddressFactory::fromKey($network, $this);
        return $address;
    }
}