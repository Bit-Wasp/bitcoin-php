<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;

class PayToPubKeyHashAddress extends Address
{
    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function getPrefixByte(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        return pack("H*", $network->getAddressByte());
    }

    /**
     * @return ScriptInterface
     */
    public function getScriptPubKey()
    {
        return ScriptFactory::scriptPubKey()->payToPubKeyHash($this->getHash());
    }
}
