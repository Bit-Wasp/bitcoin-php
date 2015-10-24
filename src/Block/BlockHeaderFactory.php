<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;

class BlockHeaderFactory
{

    /**
     * @param \BitWasp\Buffertools\Buffer|string $string
     * @return BlockHeader
     */
    public static function fromHex($string)
    {
        return (new BlockHeaderSerializer())->parse($string);
    }
}
