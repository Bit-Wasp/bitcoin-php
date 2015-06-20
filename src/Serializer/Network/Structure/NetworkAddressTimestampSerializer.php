<?php

namespace BitWasp\Bitcoin\Serializer\Network\Structure;

use BitWasp\Bitcoin\Network\Structure\NetworkAddressTimestamp;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class NetworkAddressTimestampSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getTemplate()
    {
        return (new TemplateFactory())
            ->uint32()
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
     * @param NetworkAddressTimestamp $addr
     * @return Buffer
     */
    public function serialize(NetworkAddressTimestamp $addr)
    {
        return $this->getTemplate()->write([
            $addr->getTimestamp(),
            $addr->getServices(),
            $this->getIpBuffer($addr->getIp()),
            $addr->getPort()
        ]);
    }

    /**
     * @param Buffer $ip
     * @return string
     * @throws \Exception
     */
    private function parseIpBuffer(Buffer $ip)
    {
        $end = $ip->slice(12, 4);

        return implode(
            ".",
            array_map(
                function ($int) {
                    return unpack("C", $int)[1];
                },
                str_split($end->getBinary(), 1)
            )
        );
    }

    /**
     * @param Parser $parser
     * @return NetworkAddressTimestamp
     */
    public function fromParser(Parser & $parser)
    {
        list ($timestamp, $services, $ipBuffer, $port) = $this->getTemplate()->parse($parser);
        return new NetworkAddressTimestamp(
            $timestamp,
            $services,
            $this->parseIpBuffer($ipBuffer),
            $port
        );
    }
}
