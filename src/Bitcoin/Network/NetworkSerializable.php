<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\NetworkMessageSerializer;

abstract class NetworkSerializable extends Serializable implements NetworkMessageInterface
{
    /**
     * @param NetworkInterface $network
     * @return \BitWasp\Bitcoin\Buffer
     */
    public function getNetworkMessage(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        $serializer = new NetworkMessageSerializer($network);
        $buffer = $serializer->serialize($this);
        return $buffer;
    }
}
