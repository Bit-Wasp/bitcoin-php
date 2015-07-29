<?php

namespace BitWasp\Bitcoin\Tests\Chain;

use BitWasp\Bitcoin\Chain\BlockHashIndex;
use BitWasp\Bitcoin\Chain\BlockHeightIndex;
use BitWasp\Bitcoin\Chain\BlockIndex;
use BitWasp\Bitcoin\Chain\BlockLocator;
use Doctrine\Common\Cache\ArrayCache;
use BitWasp\Bitcoin\Tests\AbstractTestCase;


class BlockLocatorTest extends AbstractTestCase
{
    public function testGenesis()
    {
        $index = new BlockIndex(new BlockHashIndex(new ArrayCache()), new BlockHeightIndex(new ArrayCache()));

        $genesis = $this->getBlock(0)->getHeader();
        $index->saveGenesis($this->getBlock(0)->getHeader());
        $index->save($this->getBlock(1)->getHeader());

        $locator = BlockLocator::create($index->height()->height(), $index, true);
        $this->assertEquals(2, count($locator->getHashes()));
        $this->assertEquals($genesis->getBlockHash(), $locator->getHashes()[1]->getHex());
    }

    public function test20Blocks()
    {
        $index = new BlockIndex(new BlockHashIndex(new ArrayCache()), new BlockHeightIndex(new ArrayCache()));

        $genesis = $this->getBlock(0)->getHeader();
        $index->saveGenesis($this->getBlock(0)->getHeader());
        for ($i = 1; $i < 20; $i++) {
            $index->save($this->getBlock($i)->getHeader());
        }

        // Locator should be smaller than 20, since step function kicks in after the latest ten blocks.
        $locator = BlockLocator::create($index->height()->height(), $index, true);
        $this->assertTrue(20 > count($locator->getHashes()));
        $hashes = $locator->getHashes();

        $this->assertEquals($genesis->getBlockHash(), end($hashes)->getHex());
    }

    public function testGenesisNoHashStop()
    {

        $index = new BlockIndex(new BlockHashIndex(new ArrayCache()), new BlockHeightIndex(new ArrayCache()));

        $genesis = $this->getBlock(0)->getHeader();
        $index->saveGenesis($this->getBlock(0)->getHeader());
        $index->save($this->getBlock(1)->getHeader());

        $locator = BlockLocator::create($index->height()->height(), $index, false);
        $this->assertEquals(1, count($locator->getHashes()));
        $this->assertEquals($genesis->getBlockHash(), $locator->getHashStop()->getHex());
    }
}
