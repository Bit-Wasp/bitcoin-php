<?php

declare(strict_types=1);

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
    public function getAddress(NetworkInterface $network = null): string;

    /**
     * @return BufferInterface
     */
    public function getHash(): BufferInterface;

    /**
     * @return ScriptInterface
     */
    public function getScriptPubKey(): ScriptInterface;
}
