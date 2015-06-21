<?php

namespace BitWasp\Bitcoin\Tests\Chain;


use BitWasp\Bitcoin\Chain\BlockHashIndex;
use BitWasp\Bitcoin\Chain\BlockHeightIndex;
use BitWasp\Bitcoin\Chain\BlockIndex;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use Doctrine\Common\Cache\ArrayCache;

class BlockIndexTest extends AbstractTestCase
{
    public function testIndices()
    {
        $hashIndex = new BlockHashIndex(new ArrayCache());
        $heightIndex = new BlockHeightIndex(new ArrayCache());
        $index = new BlockIndex(
            $hashIndex,
            $heightIndex
        );

        $this->assertSame($hashIndex, $index->hash());
        $this->assertSame($heightIndex, $index->height());
    }
}