<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Serializer\Network\Message\GetDataSerializer;
use BitWasp\Bitcoin\Serializer\Network\Structure\InventoryVectorSerializer;

class GetData extends AbstractInventory
{
    /**
     * @return string
     */
    public function getNetworkCommand()
    {
        return 'getdata';
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        return (new GetDataSerializer(new InventoryVectorSerializer()))->serialize($this);
    }
}
