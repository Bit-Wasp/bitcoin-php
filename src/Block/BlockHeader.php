<?php

namespace Bitcoin\Block;

use Bitcoin\Util\Buffer;
use Bitcoin\Util\Parser;

/**
 * Class BlockHeader
 * @package Block
 * @author Thomas Kerin
 */
class BlockHeader implements BlockHeaderInterface
{
    /**
     * @var int
     */
    protected $version;

    /**
     * @var string
     */
    protected $prevBlock;

    /**
     * @var string
     */
    protected $nextBlock;
    /**
     * @var string
     */
    protected $merkleRoot;

    /**
     * @var int
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
     * @param $hex
     * @return BlockHeader
     */
    public static function fromHex($hex)
    {
        $buffer = Buffer::hex($hex);
        $parser = new Parser($buffer);
        $block = new self();
        $block->fromParser($parser);
        return $block;
    }

    public function __construct()
    {
        return $this;
    }

    /**
     * @param Parser $parser
     * @return $this
     * @throws \Exception
     */
    public function fromParser(Parser &$parser)
    {
        $this->setVersion($parser->readBytes(4, true)->serialize('int'));
        $this->setPrevBlock($parser->readBytes(32, true));
        $this->setMerkleRoot($parser->readBytes(32, true));
        $this->setTimestamp($parser->readBytes(4, true)->serialize('int'));
        $this->setBits($parser->readBytes(4));
        $this->setNonce($parser->readBytes(4));
        return $this;
    }

    /**
     * @param $type
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
     * @return mixed
     */
    public function getBits()
    {
        return $this->bits;
    }

    /**
     * @param mixed $bits
     */
    public function setBits($bits)
    {
        $this->bits = $bits;
    }

    /**
     * @return mixed
     */
    public function getMerkleRoot()
    {
        return $this->merkleRoot;
    }

    /**
     * @param mixed $merkleRoot
     */
    public function setMerkleRoot($merkleRoot)
    {
        $this->merkleRoot = $merkleRoot;
    }

    /**
     * @return mixed
     */
    public function getPrevBlock()
    {
        return $this->prevBlock;
    }

    /**
     * @param mixed $prevBlock
     */
    public function setPrevBlock($prevBlock)
    {
        $this->prevBlock = $prevBlock;
    }

    /**
     * @return string
     */
    public function getNextBlock()
    {
        return $this->nextBlock;
    }

    /**
     * @param string $nextBlock
     */
    public function setNextBlock($nextBlock)
    {
        $this->nextBlock = $nextBlock;
    }

    /**
     * @return mixed
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @param mixed $nonce
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }
}
