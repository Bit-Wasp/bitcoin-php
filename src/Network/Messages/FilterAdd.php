<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Serializer\Network\Message\FilterAddSerializer;
use BitWasp\Buffertools\Buffer;

class FilterAdd extends NetworkSerializable
{
    /**
     * @var int[]
     */
    private $filter;

    /**
     * @param int[] $vFilter
     */
    public function __construct($vFilter)
    {
        $this->filter = $vFilter;
    }

    /**
     * @return string
     */
    public function getNetworkCommand()
    {
        return 'filteradd';
    }

    /**
     * @return \int[]
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new FilterAddSerializer())->serialize($this);
    }
}
