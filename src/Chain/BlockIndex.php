<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Block\BlockHeaderInterface;

class BlockIndex
{
    /**
     * @var BlockHeightIndex
     */
    private $height;

    /**
     * @var BlockHashIndex
     */
    private $hash;

    /**
     * @param BlockHashIndex $hashIndex
     * @param BlockHeightIndex $heightIndex
     */
    public function __construct(BlockHashIndex $hashIndex, BlockHeightIndex $heightIndex)
    {
        $this->hash = $hashIndex;
        $this->height = $heightIndex;
    }

    /**
     * @return BlockHashIndex
     */
    public function hash()
    {
        return $this->hash;
    }

    /**
     * @return BlockHeightIndex
     */
    public function height()
    {
        return $this->height;
    }

    /**
     * @param BlockHeaderInterface $header
     * @return $this
     */
    public function saveGenesis(BlockHeaderInterface $header)
    {
        try {
            $this->hash()->height();
        } catch (\Exception $e) {
            $this->hash()->saveGenesis($header);
            $this->height()->saveGenesis($header);
        }

        return $this;
    }

    /**
     * @param BlockHeaderInterface $header
     * @return $this
     * @throws \Exception
     */
    public function save(BlockHeaderInterface $header)
    {
        $this->height()->save($header);
        $this->hash()->save($header);
        return $this;
    }

    /**
     * @param int $forkStartHeight
     * @param BlockHeaderInterface[] $newHeaders
     * @return $this
     * @throws \Exception
     */
    public function reorg($forkStartHeight, array $newHeaders)
    {
        $chainHeight = $this->height()->height();
        for ($i = $forkStartHeight; $i < $chainHeight; $i++) {
            $this->deleteByHeight($i);
        }

        foreach ($newHeaders as $header) {
            $this->save($header);
        }

        return $this;
    }

    /**
     * @param BlockHeaderInterface $header
     * @return $this
     */
    public function delete(BlockHeaderInterface $header)
    {
        $hash = $header->getBlockHash();
        $this->hash()->delete($this->height()->fetch($hash));
        $this->height()->delete($hash);
        return $this;
    }

    /**
     * @param int $height
     * @return $this
     */
    public function deleteByHeight($height)
    {
        $this->height()->delete($this->hash()->fetch($height));
        $this->hash()->delete($height);
        return $this;
    }
}
