<?php

namespace BitWasp\Bitcoin\Tests\Block;


use BitWasp\Bitcoin\Block\GenesisMiningBlockHeader;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class GenesisMiningBlockHeaderTest extends AbstractTestCase
{
    public function testGetPrevBlock()
    {
        $header = new GenesisMiningBlockHeader();
        $this->assertEquals('0000000000000000000000000000000000000000000000000000000000000000', $header->getBlockHash());
    }
}