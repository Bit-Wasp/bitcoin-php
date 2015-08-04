<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Key;

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Serializable;

abstract class Key extends Serializable implements KeyInterface
{
    /**
     * @return \BitWasp\Bitcoin\Address\PayToPubKeyHashAddress
     */
    public function getAddress()
    {
        $address = AddressFactory::fromKey($this);
        return $address;
    }
}
