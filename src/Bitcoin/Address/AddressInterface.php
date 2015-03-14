<?php

namespace Afk11\Bitcoin\Address;
use Afk11\Bitcoin\Network\NetworkInterface;
interface AddressInterface
{
    /**
     * @return string
     */
    public function getPrefixByte(NetworkInterface $network);

    /**
     * @return string
     */
    public function getAddress(NetworkInterface $network);

    /**
     * @return string
     */
    public function getHash();
}
