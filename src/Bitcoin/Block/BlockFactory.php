<?php

namespace Afk11\Bitcoin\Block;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use Afk11\Bitcoin\Serializer\Block\HexBlockSerializer;
use Afk11\Bitcoin\Serializer\Transaction\TransactionCollectionSerializer;
use Afk11\Bitcoin\Serializer\Transaction\TransactionSerializer;

class BlockFactory
{
    /**
     * @return HexBlockSerializer
     */
    public static function getSerializer(Math $math = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $serializer = new HexBlockSerializer($math, new HexBlockHeaderSerializer(), new TransactionCollectionSerializer(new TransactionSerializer()));
        return $serializer;
    }

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
     * @return Block
     */
    public static function fromHex($string)
    {
        $serializer = self::getSerializer();
        $block = $serializer->parse($string);
        return $block;
    }
}
