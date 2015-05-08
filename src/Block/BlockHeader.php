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
    protected $version;

    /**
     * @var string
     */
    protected $prevBlock;

    /**
     * @var string
     */
    protected $merkleRoot;

    /**
     * @var int|string
     */
    protected $timestamp;

    /**
     * @var Buffer
     */
    protected $bits;

    /**
     * @var int
     */
    protected $nonce;


    /**
     * @var null|string
     */
    protected $nextBlock;

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
        $this->version = $version;
        $this->prevBlock = $prevBlock;
        $this->merkleRoot = $merkleRoot;
        $this->timestamp = $timestamp;
        $this->bits = $bits;
        $this->nonce = $nonce;
    }

    /**
     * Return the bits for this block
     *
     * @return null|Buffer
     */
    public function getBits()
    {
        return $this->bits;
    }

    /**
     * @return mixed
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
     * Return the Merkle root from the header
     *
     * @return null|string
     */
    public function getMerkleRoot()
    {
        return $this->merkleRoot;
    }

    /**
     * Return the previous blocks hash
     *
     * @return null|string
     */
    public function getPrevBlock()
    {
        return $this->prevBlock;
    }

    /**
     * Get the next block hash
     *
     * @return string
     */
    public function getNextBlock()
    {
        if (null == $this->nextBlock) {
            throw new \RuntimeException('Next block not known');
        }

        return $this->nextBlock;
    }

    /**
     * Set the next block hash
     *
     * @param $nextBlock
     * @return $this
     */
    public function setNextBlock($nextBlock)
    {
        $this->nextBlock = $nextBlock;
        return $this;
    }

    /**
     * Return the nonce from this block. This is the value which
     * is iterated while mining.
     *
     * @return null|integer
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * Set the nonce for this block
     *
     * @param $nonce
     * @return $this
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;
        return $this;
    }

    /**
     * Get the timestamp for this block
     *
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Get the version for this block
     *
     * @return int
     */
    public function getVersion()
    {
        if ($this->version === null) {
            return BlockHeaderInterface::CURRENT_VERSION;
        }
        return $this->version;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        $serializer = new HexBlockHeaderSerializer();
        $hex = $serializer->serialize($this);
        return $hex;
    }
}
