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
        $this->assertEquals(0, $blockStorage->size());
        $this->assertEquals(false, $blockStorage->contains(0));
    }

    public function testWithBlock()
    {
        $genesis = $this->getGenesisBlock();
        $blockStorage = new BlockStorage(new ArrayCache());
        $blockStorage->save($genesis);

        $hash = $genesis->getHeader()->getBlockHash();
        $this->assertTrue($blockStorage->contains($hash));
        $this->assertEquals(1, $blockStorage->size());

        $fetched = $blockStorage->fetch($hash);
        $this->assertEquals($genesis, $fetched);

        $blockStorage->delete($hash);
        $this->assertEquals(0, $blockStorage->size());
    }
}
