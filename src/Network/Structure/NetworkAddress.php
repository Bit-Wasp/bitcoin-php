<?php

namespace BitWasp\Bitcoin\Network\Structure;

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
    public function getIpBuffer()
    {
        $hex = (string)dechex(ip2long($this->ip));
        $hex = (strlen($hex) % 2 == 1) ? '0' . $hex : $hex;
        $hex = '00000000000000000000'.'ffff' . $hex;
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
            ->writeBytes(8, $this->services, true)
            ->writeBytes(16, $this->getIpBuffer())
            ->writeInt(2, $this->port);

        return $parser->getBuffer();
    }
}
