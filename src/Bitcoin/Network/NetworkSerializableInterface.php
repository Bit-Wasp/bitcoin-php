<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Bitcoin\SerializableInterface;

interface NetworkSerializableInterface extends SerializableInterface
{
    /**
     * @return string
     */
    public function getNetworkCommand();

    /**
     * @return NetworkMessage
     */
    public function getNetworkMessage();
}
