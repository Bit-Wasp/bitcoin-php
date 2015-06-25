<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use Doctrine\Common\Cache\Cache;

class HeaderStorage
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
     * @param BlockHeaderInterface $blk
     * @return string
     */
    private function cacheIndexBlk(BlockHeaderInterface $blk)
    {
        return $this->cacheIndex($blk->getBlockHash());
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
        $this->size--;
        return $this->blocks->delete($this->cacheIndex($hash));
    }

    /**
     * @param string $hash
     * @return BlockHeaderInterface
     */
    public function fetch($hash)
    {
        return $this->blocks->fetch($this->cacheIndex($hash));
    }

    /**
     * @param BlockHeaderInterface $block
     * @return bool
     */
    public function save(BlockHeaderInterface $block)
    {
        $key = $this->cacheIndexBlk($block);
        $this->blocks->save($key, $block);
        $this->size++;
        return $this;
    }
}
