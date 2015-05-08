<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Block\HexBlockSerializer;
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
        $serializer = new HexBlockSerializer(
            $math ?: Bitcoin::getMath(),
            new HexBlockHeaderSerializer(),
            new TransactionSerializer()
        );

        return $serializer->parse($string);
    }
}
