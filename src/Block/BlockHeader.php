<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;

class BlockHeader extends Serializable implements BlockHeaderInterface
{
    /**
     * @var int|string
     */
    private $version;

    /**
     * @var string
     */
    private $prevBlock;

    /**
     * @var string
     */
    private $merkleRoot;

    /**
     * @var int|string
     */
    private $timestamp;

    /**
     * @var Buffer
     */
    private $bits;

    /**
     * @var int|string
     */
    private $nonce;

    /**
     * @var null|string
     */
    private $nextBlock;

    /**
     * @param int|string $version
     * @param string $prevBlock
     * @param string $merkleRoot
     * @param int|string $timestamp
     * @param Buffer $bits
     * @param int|string $nonce
     */
    public function __construct($version, $prevBlock, $merkleRoot, $timestamp, Buffer $bits, $nonce)
    {
        if (!is_numeric($version)) {
            throw new \InvalidArgumentException('Block header version must be numeric');
        }

        $this->version = $version;
        $this->prevBlock = $prevBlock;
        $this->merkleRoot = $merkleRoot;
        $this->timestamp = $timestamp;
        $this->bits = $bits;
        $this->nonce = $nonce;
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
     * @see \BitWasp\Bitcoin\Block\BlockHeaderInterface::getBlockHash()
     */
    public function getBlockHash()
    {
        $parser = new Parser();
        return $parser
            ->writeBytes(32, Hash::sha256d($this->getBuffer()), true)
            ->getBuffer()
            ->getHex();
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
     * @see \BitWasp\Bitcoin\Block\BlockHeaderInterface::getPrevBlock()
     */
    public function getPrevBlock()
    {
        return $this->prevBlock;
    }

    /**
     * Get the next block hash. Cannot be required at constructor, not always known.
     *
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Block\BlockHeaderInterface::getNextBlock()
     * @throws \RuntimeException
     */
    public function getNextBlock()
    {
        if (null === $this->nextBlock) {
            throw new \RuntimeException('Next block not known');
        }

        return $this->nextBlock;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Block\BlockHeaderInterface::setNextBlock()
     */
    public function setNextBlock($nextBlock)
    {
        $this->nextBlock = $nextBlock;
        return $this;
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
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Block\BlockHeaderInterface::setNonce()
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;
        return $this;
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
     * @see \BitWasp\Buffertools\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        $serializer = new HexBlockHeaderSerializer();
        $hex = $serializer->serialize($this);
        return $hex;
    }
}
