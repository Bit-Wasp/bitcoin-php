<?php

namespace BitWasp\Bitcoin\Serializer\Network\Message;

use BitWasp\Bitcoin\Network\Messages\GetBlocks;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class GetBlocksSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->uint32le()
            ->vector(function (Parser & $parser) {
                return $parser->readBytes(32);
            })
            ->bytestring(32)
            ->getTemplate();
    }

    /**
     * @param Parser $parser
     * @return GetBlocks
     */
    public function fromParser(Parser & $parser)
    {
        list ($version, $hashes, $hashStop) = $this->getTemplate()->parse($parser);

        return new GetBlocks(
            $version,
            $hashes,
            $hashStop
        );
    }

    /**
     * @param $data
     * @return GetBlocks
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param GetBlocks $msg
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(GetBlocks $msg)
    {
        return $this->getTemplate()->write([
            $msg->getVersion(),
            $msg->getHashes(),
            $msg->getHashStop()
        ]);
    }
}
