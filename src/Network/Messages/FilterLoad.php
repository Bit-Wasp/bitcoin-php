<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 25/06/15
 * Time: 04:29
 */

namespace BitWasp\Bitcoin\Network\Messages;


use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Serializer\Network\Message\FilterLoadSerializer;
use BitWasp\Buffertools\Buffer;

class FilterLoad extends NetworkSerializable
{
    /**
     * @var int[]
     */
    private $filter;

    /**
     * @var int
     */
    private $nHashFxns;

    /**
     * @var int
     */
    private $nTweak;

    /**
     * @var Flags
     */
    private $flags;

    /**
     * @param int[] $vFilter
     * @param int $nHashFuncs
     * @param int $nTweak
     * @param Flags $flags
     */
    public function __construct($vFilter, $nHashFuncs, $nTweak, Flags $flags)
    {
        $this->filter = $vFilter;
        $this->nHashFxns = $nHashFuncs;
        $this->nTweak = $nTweak;
        $this->flags = $flags;
    }

    /**
     * @return string
     */
    public function getNetworkCommand()
    {
        return 'filterload';
    }

    /**
     * @return \int[]
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @return int
     */
    public function getNumHashFuncs()
    {
        return $this->nHashFxns;
    }

    /**
     * @return int
     */
    public function getTweak()
    {
        return $this->nTweak;
    }

    /**
     * @return Flags
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new FilterLoadSerializer())->serialize($this);
    }
}