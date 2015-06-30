<?php

namespace BitWasp\Bitcoin\Tests\Chain;

use BitWasp\Bitcoin\Chain\BlockStorage;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use Doctrine\Common\Cache\ArrayCache;

class BlockStorageTest extends AbstractTestCase
{
    public function testInstance()
    {
        $blockStorage = new BlockStorage(new ArrayCache());
        $this->assertEquals(false, $blockStorage->contains(0));
    }

    /**
     * @expectedException \Exception
     */
    public function testSizeRequiresGenesis()
    {
        $blockStorage = new BlockStorage(new ArrayCache());
        $this->assertEquals(false, $blockStorage->contains(0));
        $blockStorage->size();
    }

    public function testWithBlock()
    {
        $genesis = $this->getGenesisBlock();
        $blockStorage = new BlockStorage(new ArrayCache());
        $blockStorage->saveGenesis($genesis);

        $hash = $genesis->getHeader()->getBlockHash();
        $this->assertTrue($blockStorage->contains($hash));
        $this->assertEquals(0, $blockStorage->size());

        $fetched = $blockStorage->fetch($hash);
        $this->assertEquals($genesis, $fetched);

        $blk = $this->getBlock(1);
        $blockStorage->save($blk);
        $hash = $blk->getHeader()->getBlockHash();
        $this->assertTrue($blockStorage->contains($hash));
        $this->assertEquals(1, $blockStorage->size());

        $blockStorage->delete($hash);
        $this->assertEquals(0, $blockStorage->size());
    }
}
