<?php

namespace BitWasp\Bitcoin\Network\Structure;

use BitWasp\Bitcoin\Serializer\Network\Structure\NetworkAddressSerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Serializable;

class NetworkAddress extends Serializable
{
    /**
     * @var Buffer
     */
    private $services;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var int|string
     */
    private $port;

    /**
     * @param Buffer $services
     * @param $ip
     * @param $port
     */
    public function __construct(Buffer $services, $ip, $port)
    {
        $this->services = $services;
        $this->ip = $ip;
        $this->port = $port;
    }

    /**
     * @return int|string
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @return int|string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new NetworkAddressSerializer())->serialize($this);
    }
}
