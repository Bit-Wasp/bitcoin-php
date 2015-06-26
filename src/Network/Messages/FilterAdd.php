<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Serializer\Network\Message\FilterAddSerializer;
use BitWasp\Buffertools\Buffer;

class FilterAdd extends NetworkSerializable
{
    /**
     * @var Buffer
     */
    private $data;

    /**
     * @param Buffer $data
     */
    public function __construct(Buffer $data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getNetworkCommand()
    {
        return 'filteradd';
    }

    /**
     * @return Buffer
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new FilterAddSerializer())->serialize($this);
    }
}
