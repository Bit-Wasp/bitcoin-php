<?php

namespace Afk11\Bitcoin\Block;

use Afk11\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;

class BlockHeaderFactory
{

    /**
     * @param $string
     * @return BlockHeader
     */
    public static function fromHex($string)
    {
        $serializer = new HexBlockHeaderSerializer();
        $block = $serializer->parse($string);
        return $block;
    }
}
