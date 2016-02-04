<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;

class BlockHeaderFactory
{

    /**
     * @param \BitWasp\Buffertools\BufferInterface|string $string
     * @return BlockHeader
     */
    public static function fromHex($string)
    {
        return (new BlockHeaderSerializer())->parse($string);
    }
}
