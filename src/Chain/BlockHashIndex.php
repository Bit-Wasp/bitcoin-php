<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use Doctrine\Common\Cache\Cache;

class BlockHashIndex
{
    /**
     * @var Cache
     */
    private $index;

    /**
     * @var int
     */
    private $height;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->index = $cache;
    }

    /**
     * @param $height
     * @return string
     */
    private function cacheIndex($height)
    {
        return "blkhash_{$height}";
    }

    /**
     * @param BlockHeaderInterface $header
     */
    public function saveGenesis(BlockHeaderInterface $header)
    {
        $this->index->save($this->cacheIndex(0), $header->getBlockHash());
        $this->height = 0;
    }

    /**
     * @return int
     */
    public function height()
    {
        return $this->height;
    }

    /**
     * @param BlockHeaderInterface $header
     * @return $this
     * @throws \Exception
     */
    public function save(BlockHeaderInterface $header)
    {
        if (null === $this->height) {
            throw new \Exception('Index not initialized with genesis block');
        }
        $key = $this->cacheIndex(++$this->height);
        $this->index->save($key, $header->getBlockHash());
        return $this;
    }

    /**
     * @param $height
     * @return bool
     */
    public function contains($height)
    {
        return $this->index->contains($this->cacheIndex($height));
    }

    /**
     * @param $height
     * @return bool
     */
    public function delete($height)
    {
        $this->height--;
        return $this->index->delete($this->cacheIndex($height));
    }

    /**
     * @param $height
     * @return mixed
     */
    public function fetch($height)
    {
        return $this->index->fetch($this->cacheIndex($height));
    }
}
