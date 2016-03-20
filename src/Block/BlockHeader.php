<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\CommonTrait\FunctionAliasArrayAccess;

class BlockHeader extends Serializable implements BlockHeaderInterface
{
    use FunctionAliasArrayAccess;

    /**
     * @var int|string
     */
    private $version;

    /**
     * @var BufferInterface
     */
    private $prevBlock;

    /**
     * @var BufferInterface
     */
    private $merkleRoot;

    /**
     * @var int|string
     */
    private $timestamp;

    /**
     * @var BufferInterface
     */
    private $bits;

    /**
     * @var int|string
     */
    private $nonce;

    /**
     * @param int|string $version
     * @param BufferInterface $prevBlock
     * @param BufferInterface $merkleRoot
     * @param int|string $timestamp
     * @param BufferInterface $bits
     * @param int|string $nonce
     */
    public function __construct($version, BufferInterface $prevBlock, BufferInterface $merkleRoot, $timestamp, BufferInterface $bits, $nonce)
    {
        if ($prevBlock->getSize() !== 32) {
            throw new \InvalidArgumentException('BlockHeader prevBlock must be a 32-byte Buffer');
        }

        if ($merkleRoot->getSize() !== 32) {
            throw new \InvalidArgumentException('BlockHeader merkleRoot must be a 32-byte Buffer');
        }

        $this->version = $version;
        $this->prevBlock = $prevBlock;
        $this->merkleRoot = $merkleRoot;
        $this->timestamp = $timestamp;
        $this->bits = $bits;
        $this->nonce = $nonce;

        $this
            ->initFunctionAlias('version', 'getVersion')
            ->initFunctionAlias('prevBlock', 'getPrevBlock')
            ->initFunctionAlias('merkleRoot', 'getMerkleRoot')
            ->initFunctionAlias('timestamp', 'getTimestamp')
            ->initFunctionAlias('bits', 'getBits')
            ->initFunctionAlias('nonce', 'getNonce');
    }

    /**
     * @return BufferInterface
     */
    public function getHash()
    {
        return Hash::sha256d($this->getBuffer())->flip();
    }

    /**
     * Get the version for this block
     *
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Block\BlockHeaderInterface::getVersion()
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Block\BlockHeaderInterface::getPrevBlock()
     */
    public function getPrevBlock()
    {
        return $this->prevBlock;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Block\BlockHeaderInterface::getMerkleRoot()
     */
    public function getMerkleRoot()
    {
        return $this->merkleRoot;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Block\BlockHeaderInterface::getBits()
     */
    public function getBits()
    {
        return $this->bits;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Block\BlockHeaderInterface::getNonce()
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * Get the timestamp for this block
     *
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Block\BlockHeaderInterface::getTimestamp()
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Buffertools\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        return (new BlockHeaderSerializer())->serialize($this);
    }
}
