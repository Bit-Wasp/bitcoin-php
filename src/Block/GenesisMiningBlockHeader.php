<?php

namespace BitWasp\Bitcoin\Block;

class GenesisMiningBlockHeader extends BlockHeader
{
    /**
     * @return string
     */
    public function getBlockHash()
    {
        return '0000000000000000000000000000000000000000000000000000000000000000';
    }
}
