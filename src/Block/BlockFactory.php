<?php

namespace Afk11\Bitcoin\Block;

use Afk11\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use Afk11\Bitcoin\Serializer\Block\HexBlockSerializer;
use Afk11\Bitcoin\Serializer\Transaction\TransactionCollectionSerializer;
use Afk11\Bitcoin\Serializer\Transaction\TransactionSerializer;

class BlockFactory
{
    /**
     * @param $string
     * @return Block
     */
    public static function fromHex($string)
    {
        $serializer = new HexBlockSerializer(new HexBlockHeaderSerializer(), new TransactionCollectionSerializer(new TransactionSerializer()));
        $block = $serializer->parse($string);
        return $block;
    }
}
