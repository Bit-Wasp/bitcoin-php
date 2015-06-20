<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Block\BlockInterface;
use Doctrine\Common\Cache\Cache;

class BlockStorage
{
    /**
     * @var Cache
     */
    private $blocks;

    /**
     * @var int
     */
    private $size = 0;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->blocks = $cache;
    }

    /**
     * @param string $blkHash
     * @return string
     */
    private function cacheIndex($blkHash)
    {
        return "blk_{$blkHash}";
    }

    /**
     * @param BlockInterface $blk
     * @return string
     */
    private function cacheIndexBlk(BlockInterface $blk)
    {
        return $this->cacheIndex($blk->getHeader()->getBlockHash());
    }

    /**
     * @return int
     */
    public function size()
    {
        return $this->size;
    }

    /**
     * @param string $hash
     * @return bool
     */
    public function contains($hash)
    {
        return $this->blocks->contains($this->cacheIndex($hash));
    }

    /**
     * @param string $hash
     * @return bool
     */
    public function delete($hash)
    {
        return $this->blocks->delete($this->cacheIndex($hash));
    }

    /**
     * @param string $hash
     * @return BlockInterface
     */
    public function fetch($hash)
    {
        return $this->blocks->fetch($this->cacheIndex($hash));
    }

    /**
     * @param BlockInterface $block
     * @return bool
     */
    public function save(BlockInterface $block)
    {
        $key = $this->cacheIndexBlk($block);
        $this->blocks->save($key, $block);
        $this->size++;
        return $this;
    }
}
