<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Block\BlockSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;

class BlockFactory
{
    /**
     * @param $string
     * @param Math $math
     * @return Block
     */
    public static function fromHex($string, Math $math = null)
    {
        $serializer = new BlockSerializer(
            $math ?: Bitcoin::getMath(),
            new BlockHeaderSerializer(),
            new TransactionSerializer()
        );

        return $serializer->parse($string);
    }
}
