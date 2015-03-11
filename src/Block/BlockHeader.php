<?php

namespace Afk11\Bitcoin\Block;

use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Crypto\Hash;
use Afk11\Bitcoin\Exceptions\ParserOutOfRange;
use Afk11\Bitcoin\SerializableInterface;
use Afk11\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;

class BlockHeader implements BlockHeaderInterface
{
    /**
     * @var null|int
     */
    protected $version;

    /**
     * @var null|string
     */
    protected $prevBlock;

    /**
     * @var null|string
     */
    protected $nextBlock;

    /**
     * @var null|string
     */
    protected $merkleRoot;

    /**
     * @var null|int
     */
    protected $timestamp;

    /**
     * @var null|Buffer
     */
    protected $bits;

    /**
     * @var null|int
     */
    protected $nonce;

    /**
     * Instantiate class
     */
    public function __construct()
    {
    }

    /**
     * Return the bits for this block
     *
     * @return mixed
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
        $header = pack("H*", $this->getBuffer());
        $hash   = Buffer::hex(Hash::sha256d($header));
        $parser = new Parser();
        $parser->writeBytes(32, $hash, true);
        return $parser->getBuffer()->serialize('hex');
    }

    /**
     * @param mixed $bits
     * @return $this
     */
    public function setBits(Buffer $bits)
    {
        $this->bits = $bits;
        return $this;
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
     * Set the merkle root.
     *
     * @param $merkleRoot
     * @return $this
     */
    public function setMerkleRoot($merkleRoot)
    {
        $this->merkleRoot = $merkleRoot;
        return $this;
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
     * Set the previous blocks hash
     *
     * @param string $prevBlock
     * @return $this
     */
    public function setPrevBlock($prevBlock)
    {
        $this->prevBlock = $prevBlock;
        return $this;
    }

    /**
     * Get the next block hash
     *
     * @return string
     */
    public function getNextBlock()
    {
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
     * @return mixed
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
     * Set the timestamp for this block
     *
     * @param $timestamp
     * @return $this
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * Get the version for this block
     *
     * @return int
     */
    public function getVersion()
    {
        if ($this->version == null) {
            return BlockHeaderInterface::CURRENT_VERSION;
        }
        return $this->version;
    }

    /**
     * Set the version of this block
     *
     * @param $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
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
