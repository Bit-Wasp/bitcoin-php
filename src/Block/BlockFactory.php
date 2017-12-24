<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Block\BlockSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Buffertools\BufferInterface;

class BlockFactory
{
    /**
     * @param BufferInterface|string $string
     * @param Math $math
     * @return BlockInterface
     */
    public static function fromHex($string, Math $math = null): BlockInterface
    {
        $serializer = new BlockSerializer(
            $math ?: Bitcoin::getMath(),
            new BlockHeaderSerializer(),
            new TransactionSerializer()
        );

        return $serializer->parse($string);
    }
}
