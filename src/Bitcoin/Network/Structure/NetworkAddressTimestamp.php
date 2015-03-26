<?php

namespace BitWasp\Bitcoin\Network\Structure;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Parser;

class NetworkAddressTimestamp extends NetworkAddress
{
    /**
     * @var int|string
     */
    protected $time;

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
     * @return Buffer
     */
    public function getBuffer()
    {
        $parser = new Parser();
        $parser
            ->writeInt(4, $this->getTimestamp(), true)
            ->writeBytes(8, $this->getServices(), true)
            ->writeBytes(16, $this->getIpBuffer())
            ->writeInt(2, $this->port);

        return $parser->getBuffer();
    }
}
