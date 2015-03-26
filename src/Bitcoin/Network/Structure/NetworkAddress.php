<?php

namespace BitWasp\Bitcoin\Network\Structure;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Serializable;

class NetworkAddress extends Serializable
{
    /**
     * @var int|string
     */
    protected $services;

    /**
     * @var string
     */
    protected $ip;

    /**
     * @var int|string
     */
    protected $port;

    /**
     * @param Buffer $services
     * @param $ip
     * @param $port
     */
    public function __construct(Buffer $services, $ip, $port)
    {
        $this->services = $services->getInt();
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
    public function getIpBuffer()
    {
        $ip_split = explode(".", $this->ip);
        $hex = sprintf("%02x%02x%02x%02x", $ip_split[0], $ip_split[1], $ip_split[2], $ip_split[3]);
        $buffer = Buffer::hex($hex);
        return $buffer;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        $parser = new Parser();
        $parser
            ->writeInt(8, $this->services, true)
            ->writeInt(16, $this->getIpBuffer())
            ->writeInt(2, $this->port, true);

        return $parser->getBuffer();
    }
}