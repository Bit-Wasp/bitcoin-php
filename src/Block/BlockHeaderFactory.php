<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;

class BlockHeaderFactory
{

    /**
     * @param $string
     * @return BlockHeader
     */
    public static function fromHex($string)
    {
        $serializer = new BlockHeaderSerializer();
        $block = $serializer->parse($string);
        return $block;
    }
}
