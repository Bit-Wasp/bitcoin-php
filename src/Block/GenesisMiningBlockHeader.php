<?php

namespace Afk11\Bitcoin\Block;

/**
 * Class GenesisMiningBlockHeader
 * @package Bitcoin\Block
 */
class GenesisMiningBlockHeader extends BlockHeader
{
    public function getBlockHash()
    {
        return '0000000000000000000000000000000000000000000000000000000000000000';
    }
}
