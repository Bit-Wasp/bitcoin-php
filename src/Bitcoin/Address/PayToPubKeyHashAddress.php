<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Key\KeyInterface;

class PayToPubKeyHashAddress extends Address
{
    /**
     * @var KeyInterface
     */
    private $key;

    /**
     * @param KeyInterface $key
     * @internal param NetworkInterface $network
     */
    public function __construct(KeyInterface $key)
    {
        $this->key = $key;
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function getPrefixByte(NetworkInterface $network)
    {
        return $network->getAddressByte();
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->key->getPubKeyHash();
    }
}
