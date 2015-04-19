<?php

namespace BitWasp\Bitcoin\Block;

class GenesisMiningBlockHeader extends BlockHeader
{
    public function getBlockHash()
    {
        return '0000000000000000000000000000000000000000000000000000000000000000';
    }
}
