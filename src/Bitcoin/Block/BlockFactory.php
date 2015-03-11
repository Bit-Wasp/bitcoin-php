<?php

namespace Afk11\Bitcoin\Block;

use Afk11\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use Afk11\Bitcoin\Serializer\Block\HexBlockSerializer;
use Afk11\Bitcoin\Serializer\Transaction\TransactionCollectionSerializer;
use Afk11\Bitcoin\Serializer\Transaction\TransactionSerializer;

class BlockFactory
{
    /**
     * @return HexBlockSerializer
     */
    public static function getSerializer()
    {
        $serializer = new HexBlockSerializer(new HexBlockHeaderSerializer(), new TransactionCollectionSerializer(new TransactionSerializer()));
        return $serializer;
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
