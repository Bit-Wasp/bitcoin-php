<?php

namespace Bitcoin\Block;

use Bitcoin\Block\BlockHeaderInterface;
use Bitcoin\Block\BlockHeader;
use Bitcoin\Util\Buffer;
use Bitcoin\Util\Parser;

class Block implements BlockInterface
{
    /**
     * @var Buffer
     */
    protected $magicBytes;

    /**
     * @var BlockHeader
     */
    protected $header;

    /**
     * @var array
     */
    protected $transactions = array();

    /**
     * @param Parser $parser
     * @return Block
     */
    public function fromParser(Parser &$parser)
    {
        $block = new self();

        $header = new BlockHeader();
        $header->fromParser($parser);
        $block->setHeader($header);
        $block->setTransactions(
            $parser->getArray(
                function () use (&$parser) {
                    $transaction = new \Bitcoin\Transaction\Transaction();
                    $transaction->fromParser($parser);
                    return $transaction;
                }
            )
        );
            return $block;
    }

    /**
     * @param $hex
     * @return Block
     */
    public static function fromHex($hex)
    {
        $buffer = Buffer::hex($hex);
        $parser = new Parser($buffer);
        return self::fromParser($parser);
    }

    /**
     *
     */
    public function __construct()
    {
        $this->header = new BlockHeader();
        return $this;
    }

    /**
     * @return BlockHeader
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param BlockHeaderInterface $header
     * @return $this
     */
    public function setHeader(BlockHeaderInterface $header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getMerkleRoot()
    {
        $root = new MerkleRoot($this);
        return $root->calculateHash();
    }

    /**
     * @return array
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param array $array
     * @return $this
     */
    public function setTransactions(array $array)
    {
        $this->transactions = $array;
        return $this;
    }
}
