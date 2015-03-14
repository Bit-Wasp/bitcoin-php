<?php

namespace Afk11\Bitcoin\Address;
use Afk11\Bitcoin\Network\NetworkInterface;

interface AddressInterface
{
    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function getPrefixByte(NetworkInterface $network);

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function getAddress(NetworkInterface $network);

    /**
     * @return string
     */
    public function getHash();
}
