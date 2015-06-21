<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Serializer\Network\Message\NotFoundSerializer;
use BitWasp\Bitcoin\Serializer\Network\Structure\InventoryVectorSerializer;

;

class NotFound extends AbstractInventory
{
    /**
     * @return string
     */
    public function getNetworkCommand()
    {
        return 'notfound';
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        return (new NotFoundSerializer(new InventoryVectorSerializer()))->serialize($this);
    }
}
