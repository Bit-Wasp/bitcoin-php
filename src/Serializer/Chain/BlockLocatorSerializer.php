<?php

namespace BitWasp\Bitcoin\Serializer\Chain;

use BitWasp\Bitcoin\Chain\BlockLocator;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Template;

class BlockLocatorSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        $bytestring32 = Types::bytestringle(32);
        return new Template([
            Types::vector(function (Parser $parser) use ($bytestring32) {
                return $bytestring32->read($parser);
            }),
            $bytestring32
        ]);
    }

    /**
     * @param Parser $parser
     * @return BlockLocator
     */
    public function fromParser(Parser $parser)
    {
        list ($hashes, $hashStop) = $this->getTemplate()->parse($parser);

        return new BlockLocator($hashes, $hashStop);
    }

    /**
     * @param BufferInterface|string $data
     * @return BlockLocator
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param BlockLocator $blockLocator
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function serialize(BlockLocator $blockLocator)
    {
        $hashes = [];
        foreach ($blockLocator->getHashes() as $hash) {
            $hashes[] = $hash->flip();
        }

        return $this->getTemplate()->write([
            $hashes,
            $blockLocator->getHashStop()
        ]);
    }
}
