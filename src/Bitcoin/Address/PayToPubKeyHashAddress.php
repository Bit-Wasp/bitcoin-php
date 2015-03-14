<?php

namespace Afk11\Bitcoin\Address;

use Afk11\Bitcoin\Network\NetworkInterface;
use Afk11\Bitcoin\Key\KeyInterface;

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
