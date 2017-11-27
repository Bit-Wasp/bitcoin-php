<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;

class BlockHeaderFactory
{

    /**
     * @param \BitWasp\Buffertools\BufferInterface|string $string
     * @return BlockHeaderInterface
     */
    public static function fromHex($string): BlockHeaderInterface
    {
        return (new BlockHeaderSerializer())->parse($string);
    }
}
