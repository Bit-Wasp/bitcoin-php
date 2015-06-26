<?php

namespace BitWasp\Bitcoin\Serializer\Network\Message;

use BitWasp\Bitcoin\Network\Messages\GetHeaders;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class GetHeadersSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->uint32le()
            ->vector(function (Parser & $parser) {
                return $parser->readBytes(32, true);
            })
            ->bytestringle(32)
            ->getTemplate();
    }

    /**
     * @param Parser $parser
     * @return GetHeaders
     */
    public function fromParser(Parser & $parser)
    {
        list ($version, $hashes, $hashStop) = $this->getTemplate()->parse($parser);
        $hashes[] = $hashStop;
        return new GetHeaders(
            $version,
            $hashes
        );
    }

    /**
     * @param $data
     * @return GetHeaders
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param GetHeaders $msg
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(GetHeaders $msg)
    {
        $hashes = [];
        foreach ($msg->getHashes() as $hash) {
            $flipped = new Buffer(Buffertools::flipBytes($hash->getBinary()));
            $hashes[] = $flipped;
        }

        return $this->getTemplate()->write([
            $msg->getVersion(),
            $hashes,
            $msg->getHashStop()
        ]);
    }
}
