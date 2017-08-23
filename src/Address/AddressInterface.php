<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\BufferInterface;

interface AddressInterface
{
    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function getAddress(NetworkInterface $network = null);

    /**
     * @return BufferInterface
     */
    public function getHash();

    /**
     * @return ScriptInterface
     */
    public function getScriptPubKey();
}
