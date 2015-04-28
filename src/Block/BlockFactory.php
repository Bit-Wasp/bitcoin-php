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
     * @param Math $math
     * @return Block
     */
    public static function create(Math $math = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $block = new Block($math);
        return $block;
    }

    /**
     * @param $string
     * @param Math $math
     * @return Block
     */
    public static function fromHex($string, Math $math = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $serializer = new HexBlockSerializer($math, new HexBlockHeaderSerializer(), new TransactionSerializer());
        $block = $serializer->parse($string);
        return $block;
    }
}
