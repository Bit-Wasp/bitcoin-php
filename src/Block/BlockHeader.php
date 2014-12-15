<?php

namespace Bitcoin\Block;

use Bitcoin\Util\Buffer;
use Bitcoin\Util\Parser;
use Bitcoin\Crypto\Hash;
use Bitcoin\Exceptions\ParserOutOfRange;

/**
 * Class BlockHeader
 * @package Block
 * @author Thomas Kerin
 */
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
     * @param $hex
     * @return BlockHeader
     */
    public static function fromHex($hex)
    {
        $buffer = Buffer::hex($hex);
        $parser = new Parser($buffer);
        $block  = new self();
        $block->fromParser($parser);
        return $block;
    }

    /**
     * @param Parser $parser
     * @return $this
     * @throws \Bitcoin\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser &$parser)
    {
        try {
            $this->setVersion($parser->readBytes(4, true)->serialize('int'));
            $this->setPrevBlock($parser->readBytes(32, true));
            $this->setMerkleRoot($parser->readBytes(32, true));
            $this->setTimestamp($parser->readBytes(4, true)->serialize('int'));
            $this->setBits($parser->readBytes(4));
            $this->setNonce($parser->readBytes(4));
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full block header from parser');
        }

        return $this;
    }

    /**
     * Serialize the block header to binary (default) or hex.
     *
     * @param null $type
     * @return string
     */
    public function serialize($type = null)
    {
        $data = new Parser;
        $data->writeInt(4, $this->getVersion(), 2);
        $data->writeBytes(32, $this->getPrevBlock(), true);
        $data->writeBytes(32, $this->getMerkleRoot(), true);
        $data->writeInt(4, $this->getTimestamp());
        $data->writeBytes(4, $this->getBits());
        $data->writeBytes(4, $this->getNonce());

        return $data->getBuffer()->serialize($type);
    }

    /**
     * Instantiate class
     */
    public function __construct()
    {
        return $this;
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
     *
     * @return mixed
     */
    public function getBlockHash()
    {
        $header = $this->serialize();
        $hash   = Hash::sha256d($header);
        return $hash;
    }

    /**
     * @param mixed $bits
     * @return $this
     */
    public function setBits($bits)
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
        return $this->version;
    }

    /**
     * Set the version of this block
     *
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }
}
