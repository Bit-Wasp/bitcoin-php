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

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Index not initialized with genesis block
     */
    public function testErrorsBeforeGenesis()
    {
        $hashIndex = new BlockHashIndex(new ArrayCache());
        $heightIndex = new BlockHeightIndex(new ArrayCache());
        $index = new BlockIndex(
            $hashIndex,
            $heightIndex
        );

        $index->height()->height();
    }

    public function testWithGenesis()
    {
        $hashIndex = new BlockHashIndex(new ArrayCache());
        $heightIndex = new BlockHeightIndex(new ArrayCache());
        $index = new BlockIndex(
            $hashIndex,
            $heightIndex
        );

        $genesis = $this->getGenesisBlock();
        $header = $genesis->getHeader();
        $hash = $header->getBlockHash();
        $height = 0;

        $index->saveGenesis($header);
        $this->assertEquals(0, $index->height()->height());

        $this->assertEquals($hash, $index->hash()->fetch($height));
        $this->assertEquals($height, $index->height()->fetch($hash));
    }

    public function testDelete()
    {
        $hashIndex = new BlockHashIndex(new ArrayCache());
        $heightIndex = new BlockHeightIndex(new ArrayCache());
        $index = new BlockIndex(
            $hashIndex,
            $heightIndex
        );

        $genesis = $this->getGenesisBlock();
        $header = $genesis->getHeader();
        $hash = $header->getBlockHash();

        $index->saveGenesis($genesis->getHeader());
        $this->assertTrue($index->height()->contains($hash));
        $this->assertTrue($index->hash()->contains(0));

        $index->delete($genesis->getHeader());
        $this->assertFalse($index->height()->contains($hash));
        $this->assertFalse($index->hash()->contains(0));
    }

    public function testDeleteByHeight()
    {
        $hashIndex = new BlockHashIndex(new ArrayCache());
        $heightIndex = new BlockHeightIndex(new ArrayCache());
        $index = new BlockIndex(
            $hashIndex,
            $heightIndex
        );

        $genesis = $this->getGenesisBlock();
        $header = $genesis->getHeader();
        $hash = $header->getBlockHash();
        $height = 0;

        $index->saveGenesis($genesis->getHeader());
        $this->assertTrue($index->height()->contains($hash));
        $this->assertTrue($index->hash()->contains(0));

        $index->deleteByHeight($height);
        $this->assertFalse($index->height()->contains($hash));
        $this->assertFalse($index->hash()->contains(0));
    }
}
