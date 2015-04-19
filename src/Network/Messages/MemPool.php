<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\NetworkSerializable;

class MemPool extends NetworkSerializable
{
    /**
     * @see \BitWasp\Bitcoin\Network\NetworkSerializableInterface::getNetworkCommand()
     */
    public function getNetworkCommand()
    {
        return 'mempool';
    }

    /**
     * @see \BitWasp\Bitcoin\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        return new Buffer();
    }
}
