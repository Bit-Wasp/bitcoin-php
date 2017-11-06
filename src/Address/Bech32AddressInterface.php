<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Network\NetworkInterface;

interface Bech32AddressInterface extends AddressInterface
{
    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function getHRP(NetworkInterface $network = null): string;
}
