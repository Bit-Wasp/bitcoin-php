<?php

namespace Afk11\Bitcoin\Address;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Network\NetworkInterface;

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
