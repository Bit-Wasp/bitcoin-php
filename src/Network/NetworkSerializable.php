<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\NetworkMessageSerializer;

abstract class NetworkSerializable extends Serializable implements NetworkSerializableInterface
{
    /**
     * @param Network $network
     * @return NetworkMessage
     */
    public function getNetworkMessage(Network $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        $message = new NetworkMessage($network, $this);
        return $message;
    }
}
