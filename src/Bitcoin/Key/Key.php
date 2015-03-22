<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializable;

abstract class Key extends Serializable implements KeyInterface
{
    /**
     * @return \BitWasp\Bitcoin\Address\ScriptHashAddress
     */
    public function getAddress()
    {
        $address = AddressFactory::fromKey($this);
        return $address;
    }
}
