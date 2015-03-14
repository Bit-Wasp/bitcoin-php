<?php

namespace Afk11\Bitcoin\Key;

use Afk11\Bitcoin\Address\AddressFactory;
use Afk11\Bitcoin\Network\NetworkInterface;

abstract class Key implements KeyInterface
{
    /**
     * @return \Afk11\Bitcoin\Address\ScriptHashAddress
     */
    public function getAddress()
    {
        $address = AddressFactory::fromKey($this);
        return $address;
    }
}
