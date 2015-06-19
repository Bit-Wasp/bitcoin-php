<?php

namespace BitWasp\Bitcoin\Serializer\Network\Structure;


use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class NetworkAddressSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getTemplate()
    {
        return (new TemplateFactory())
            ->bytestringle(8)
            ->bytestring(16)
            ->uint16()
            ->getTemplate();
    }

    /**
     * @param string $ip
     * @return Buffer
     */
    private function getIpBuffer($ip)
    {
        $hex = (string)dechex(ip2long($ip));
        $hex = (strlen($hex) % 2 == 1) ? '0' . $hex : $hex;
        $hex = '00000000000000000000'.'ffff' . $hex;
        $buffer = Buffer::hex($hex);
        return $buffer;
    }

    /**
     * @param NetworkAddress $addr
     * @return Buffer
     */
    public function serialize(NetworkAddress $addr)
    {
        return $this->getTemplate()->write([
            $addr->getServices(),
            $this->getIpBuffer($addr->getIp()),
            $addr->getPort()
        ]);
    }

    /**
     * @param Parser $parser
     * @return NetworkAddress
     */
    public function fromParser(Parser & $parser)
    {
        list ($services, $ipBuffer, $port) = $this->getTemplate()->parse($parser);
        return new NetworkAddress(
            $services,
            $ipBuffer,
            $port
        );
    }
}