<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Buffertools\Buffer;

class FilterClear extends NetworkSerializable
{
    /**
     * @return string
     */
    public function getNetworkCommand()
    {
        return 'filterclear';
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return new Buffer();
    }
}
