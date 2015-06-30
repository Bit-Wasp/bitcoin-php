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
        $this->index->save('height', '0');
    }

    /**
     * @param BlockHeaderInterface $header
     * @return $this
     * @throws \Exception
     */
    public function save(BlockHeaderInterface $header)
    {
        $height = $this->height();
        $this->index->save('height', ++$height);

        $key = $this->cacheIndex($height);
        $this->index->save($key, $header->getBlockHash());
        return $this;
    }

    /**
     * @param int $height
     * @return bool
     */
    public function contains($height)
    {
        return $this->index->contains($this->cacheIndex($height));
    }

    /**
     * @param int $height
     * @return bool
     */
    public function delete($height)
    {
        $this->index->save('height', (string)($height - 1));
        return $this->index->delete($this->cacheIndex($height));
    }

    /**
     * @param int $height
     * @return string
     */
    public function fetch($height)
    {
        return $this->index->fetch($this->cacheIndex($height));
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function height()
    {
        $height = $this->index->fetch('height');
        if (false === $height) {
            throw new \Exception('Index not initialized with genesis block');
        }

        return $height;
    }
}
