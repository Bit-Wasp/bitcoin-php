<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Serializer\Network\Message\InvSerializer;
use BitWasp\Bitcoin\Serializer\Network\Structure\InventoryVectorSerializer;

class Inv extends AbstractInventory
{
    /**
     * @return string
     */
    public function getNetworkCommand()
    {
        return 'inv';
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        return (new InvSerializer(new InventoryVectorSerializer()))->serialize($this);
    }
}
