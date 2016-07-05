<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Buffertools\BufferInterface;

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
    public function getAddress(NetworkInterface $network = null);

    /**
     * @return BufferInterface
     */
    public function getHash();
}
