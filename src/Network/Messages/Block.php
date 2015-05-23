<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Network\NetworkSerializable;

class Block extends NetworkSerializable
{
    /**
     * @var BlockInterface
     */
    private $block;

    /**
     * @param BlockInterface $block
     */
    public function __construct(BlockInterface $block)
    {
        $this->block = $block;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Network\NetworkSerializableInterface::getNetworkCommand()
     */
    public function getNetworkCommand()
    {
        return 'block';
    }

    /**
     * @return BlockInterface
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        return $this->block->getBuffer();
    }
}
