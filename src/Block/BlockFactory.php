<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Block\BlockSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\MTransactionSerializer;

class BlockFactory
{
    /**
     * @param \BitWasp\Buffertools\Buffer|string $string
     * @param Math $math
     * @return BlockInterface
     */
    public static function fromHex($string, Math $math = null)
    {
        return (new BlockSerializer(
            $math ?: Bitcoin::getMath(),
            new BlockHeaderSerializer(),
            new MTransactionSerializer()
        ))
            ->parse($string);
    }
}
