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
        $this->index->save('height', 0);
        $this->index->save($this->cacheIndexHeader($header), 0);
    }

    /**
     * @param BlockHeaderInterface $header
     * @return $this
     * @throws \Exception
     */
    public function save(BlockHeaderInterface $header)
    {
        $height = $this->height();
        $this->index->save($this->cacheIndexHeader($header), ++$height);
        $this->index->save('height', (string)$height);
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
        $height = $this->height();
        $this->index->save('height', ($height - 1));
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
