<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use Doctrine\Common\Cache\Cache;

class BlockHeightIndex
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
     * @param $hash
     * @return string
     */
    private function cacheIndex($hash)
    {
        return "blkheight_{$hash}";
    }

    /**
     * @param BlockHeaderInterface $header
     * @return string
     */
    private function cacheIndexHeader(BlockHeaderInterface $header)
    {
        return "blkheight_" . $header->getBlockHash();
    }

    /**
     * @param BlockHeaderInterface $header
     */
    public function saveGenesis(BlockHeaderInterface $header)
    {
        $this->height = 0;
        $this->index->save($this->cacheIndexHeader($header), 0);
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
        $key = $this->cacheIndexHeader($header);
        $this->index->save($key, $this->height++);
        return $this;
    }

    /**
     * @param string $hash
     * @return bool
     */
    public function contains($hash)
    {
        return $this->index->contains($this->cacheIndex($hash));
    }

    /**
     * @param string $hash
     * @return bool
     */
    public function delete($hash)
    {
        return $this->index->delete($this->cacheIndex($hash));
    }

    /**
     * @param string $hash
     * @return mixed
     */
    public function fetch($hash)
    {
        return $this->index->fetch($this->cacheIndex($hash));
    }

    public function height()
    {
        if (null === $this->height) {
            throw new \Exception('Index not initialized with genesis block');
        }
        return $this->height;
    }
}
