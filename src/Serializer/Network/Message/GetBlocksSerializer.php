<?php

namespace BitWasp\Bitcoin\Serializer\Network\Message;

use BitWasp\Bitcoin\Network\Messages\GetBlocks;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;
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
                return $parser->readBytes(32, true);
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
        $hashes[] = $hashStop;
        return new GetBlocks(
            $version,
            $hashes
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
        $hashes = [];
        foreach ($msg->getHashes() as $hash) {
            $flipped = new Buffer(Buffertools::flipBytes($hash->getBinary()), 32);
            $hashes[] = $flipped;
        }

        return $this->getTemplate()->write([
            $msg->getVersion(),
            $hashes,
            $msg->getHashStop()
        ]);
    }
}
