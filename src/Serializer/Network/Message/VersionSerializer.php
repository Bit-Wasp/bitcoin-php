<?php

namespace BitWasp\Bitcoin\Serializer\Network\Message;

use BitWasp\Bitcoin\Network\Messages\Version;
use BitWasp\Bitcoin\Serializer\Network\Structure\NetworkAddressSerializer;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class VersionSerializer
{
    /**
     * @var NetworkAddressSerializer
     */
    private $netAddr;

    /**
     * @param NetworkAddressSerializer $netAddr
     */
    public function __construct(NetworkAddressSerializer $netAddr)
    {
        $this->netAddr = $netAddr;
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getTemplate()
    {
        return (new TemplateFactory())
            ->uint32le()      // version
            ->bytestring(8)   // services
            ->uint64le()      // timestamp
            ->bytestring(26)  // addrRecv
            ->bytestring(26)  // addrFrom
            ->uint64le()      // nonce
            ->varstring()     // user agent
            ->uint32le()      // start height
            ->uint8le()       // relay
            ->getTemplate();
    }

    /**
     * @param Parser $parser
     * @return Version
     */
    public function fromParser(Parser & $parser)
    {
        list ($version, $services, $timestamp, $addrRecv, $addrFrom, $nonce, $userAgent, $startHeight, $relay) = $this->getTemplate()->parse($parser);

        return new Version(
            $version,
            $services,
            $timestamp,
            $this->netAddr->parse($addrRecv),
            $this->netAddr->parse($addrFrom),
            $nonce,
            $userAgent,
            $startHeight,
            (bool)$relay
        );
    }

    /**
     * @param $string
     * @return Version
     */
    public function parse($string)
    {
        return $this->fromParser(new Parser($string));
    }

    /**
     * @param Version $version
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(Version $version)
    {
        return $this->getTemplate()->write([
            $version->getVersion(),
            $version->getServices(),
            $version->getTimestamp(),
            $version->getRecipientAddress()->getBuffer(),
            $version->getSenderAddress()->getBuffer(),
            $version->getNonce(),
            $version->getUserAgent(),
            $version->getStartHeight(),
            $version->getRelay()
        ]);
    }
}
