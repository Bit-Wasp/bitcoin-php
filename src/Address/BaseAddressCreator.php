<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;

abstract class BaseAddressCreator
{
    /**
     * @param string $strAddress
     * @param NetworkInterface|null $network
     * @return Address
     */
    abstract public function fromString($strAddress, NetworkInterface $network = null);

    /**
     * @param ScriptInterface $script
     * @return Address
     */
    abstract public function fromOutputScript(ScriptInterface $script);
}
