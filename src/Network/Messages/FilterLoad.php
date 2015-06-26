<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 25/06/15
 * Time: 04:29
 */

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Network\BloomFilter;
use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Serializer\Network\BloomFilterSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\FilterLoadSerializer;
use BitWasp\Buffertools\Buffer;

class FilterLoad extends NetworkSerializable
{
    /**
     * @var BloomFilter
     */
    private $filter;

    /**
     * @param BloomFilter $filter
     */
    public function __construct(BloomFilter $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @return string
     */
    public function getNetworkCommand()
    {
        return 'filterload';
    }

    /**
     * @return BloomFilter
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
        return (new FilterLoadSerializer(new BloomFilterSerializer()))->serialize($this);
    }
}
