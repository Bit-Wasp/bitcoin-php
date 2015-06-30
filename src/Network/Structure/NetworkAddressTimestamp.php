<?php

namespace BitWasp\Bitcoin\Network\Structure;

use BitWasp\Bitcoin\Serializer\Network\Structure\NetworkAddressTimestampSerializer;
use BitWasp\Buffertools\Buffer;

class NetworkAddressTimestamp extends NetworkAddress
{
    /**
     * @var int|string
     */
    private $time;

    /**
     * @param $time
     * @param Buffer $services
     * @param $ip
     * @param $port
     */
    public function __construct($time, Buffer $services, $ip, $port)
    {
        $this->time = $time;
        parent::__construct($services, $ip, $port);
    }

    /**
     * @return int|string
     */
    public function getTimestamp()
    {
        return $this->time;
    }

    /**
     * @return NetworkAddress
     */
    public function withoutTimestamp()
    {
        return new NetworkAddress(
            $this->getServices(),
            $this->getIp(),
            $this->getPort()
        );
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new NetworkAddressTimestampSerializer())->serialize($this);
    }
}
