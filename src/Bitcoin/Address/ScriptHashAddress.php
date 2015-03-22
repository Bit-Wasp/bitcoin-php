<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Network\NetworkInterface;

class ScriptHashAddress extends Address
{
    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function getPrefixByte(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        return $network->getP2shByte();
    }
}
