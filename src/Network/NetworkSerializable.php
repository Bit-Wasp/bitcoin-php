<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Serializable;

abstract class NetworkSerializable extends Serializable implements NetworkSerializableInterface
{
    /**
     * @param NetworkInterface $network
     * @return NetworkMessage
     */
    public function getNetworkMessage(NetworkInterface $network = null)
    {
        return new NetworkMessage(
            $network ?: Bitcoin::getNetwork(),
            $this
        );
    }
}
