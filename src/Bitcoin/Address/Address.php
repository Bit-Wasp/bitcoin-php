<?php

namespace Afk11\Bitcoin\Address;

use Afk11\Bitcoin\Base58;

abstract class Address implements AddressInterface
{
    public function getAddress()
    {
        return Base58::encode($this->getPrefixByte() . $this->getHash());
    }
}