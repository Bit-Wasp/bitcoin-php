<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Network\NetworkInterface;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Crypto\Hash;

class PayToPubKeyAddress extends Address
{
    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function getPrefixByte(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        return $network->getAddressByte();
    }

    /**
     * @param NetworkInterface|null $network
     * @return PayToPubKeyAddress
     */
    public function getAddress(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        $payload = Hash::sha256ripe160(Buffer::hex($this->getHash()));
        return Base58::encodeCheck(Buffer::hex($network->getAddressByte() . $payload->getHex()));
    }
}
