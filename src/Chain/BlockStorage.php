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
     * @throws \Exception
     */
    public function size()
    {
        $size = $this->blocks->fetch('size');
        if (false === $size) {
            throw new \Exception('Not initialized with genesis block');
        }

        return $size;
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
        $size = $this->size();
        $this->blocks->save('size', --$size);
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
     */
    public function saveGenesis(BlockInterface $block)
    {
        try {
            $this->size();
        } catch (\Exception $e) {
            $this->blocks->save('size', 0);
            $this->blocks->save($this->cacheIndexBlk($block), $block);
        }
    }

    /**
     * @param BlockInterface $block
     * @return bool
     */
    public function save(BlockInterface $block)
    {
        $size = $this->size();
        $key = $this->cacheIndexBlk($block);
        $this->blocks->save($key, $block);
        $this->blocks->save('size', ++$size);
        return $this;
    }
}
