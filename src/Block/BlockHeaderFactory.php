<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;

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
