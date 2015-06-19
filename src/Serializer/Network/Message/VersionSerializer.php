<?php

namespace BitWasp\Bitcoin\Serializer\Network\Message;

use BitWasp\Bitcoin\Network\Messages\Version;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class VersionSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getTemplate()
    {
        return (new TemplateFactory())
            ->uint32le()
            ->uint64le()
            ->uint64le()
            ->bytestring(26)
            ->bytestring(26)
            ->uint64le()
            ->varstring()
            ->uint32le()
            ->uint16()
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
            $addrRecv,
            $addrFrom,
            $nonce,
            $userAgent,
            $startHeight,
            $relay
        );
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
