<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Network\NetworkSerializable;

class VerAck extends NetworkSerializable
{
    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Network\NetworkSerializableInterface::getNetworkCommand()
     */
    public function getNetworkCommand()
    {
        return 'verack';
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        return new Buffer();
    }
}
