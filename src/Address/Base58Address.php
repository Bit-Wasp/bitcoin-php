<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Buffertools\Buffer;

abstract class Base58Address extends Address implements Base58AddressInterface
{
    /**
     * @param NetworkInterface|null $network
     * @return string
     */
    public function getAddress(NetworkInterface $network = null): string
    {
        $network = $network ?: Bitcoin::getNetwork();
        $payload = new Buffer($this->getPrefixByte($network) . $this->getHash()->getBinary());
        return Base58::encodeCheck($payload);
    }
}
