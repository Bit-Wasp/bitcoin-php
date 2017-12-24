<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;
use BitWasp\Buffertools\BufferInterface;

class BlockHeaderFactory
{
    /**
     * @param string|BufferInterface $string
     * @return BlockHeaderInterface
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public static function fromHex($string): BlockHeaderInterface
    {
        return (new BlockHeaderSerializer())->parse($string);
    }
}
