<?php

namespace Bitcoin\Block;

use Bitcoin\Util\Buffer;
use Bitcoin\Util\Parser;
use Bitcoin\Exceptions\ParserOutOfRange;

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
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser &$parser)
    {
        $block = new self();

        try {
            $header = new BlockHeader();
            $header->fromParser($parser);
            $this->setHeader($header);
            $this->setTransactions(
                $parser->getArray(
                    function () use (&$parser) {
                        $transaction = new \Bitcoin\Transaction\Transaction();
                        $transaction->fromParser($parser);
                        return $transaction;
                    }
                )
            );
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full block header from parser');
        }

        return $this;
    }

    public function serialize($type = null)
    {
        $header = new Buffer($this->getHeader()->serialize());
        $parser = new Parser($header);
        print_r($this->getTransactions());
        $parser->writeArray($this->getTransactions());
        return $parser->getBuffer()->serialize($type);
    }

    /**
     * @param $hex
     * @return Block
     * @throws ParserOutOfRange
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
     * Instantiate class
     */
    public function __construct()
    {
        $this->header = new BlockHeader();
        return $this;
    }

    /**
     * Return the blocks header
     * TODO: Perhaps these should only be instantiated from a full block?
     * @return BlockHeader
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Set the header for this block
     *
     * @param BlockHeaderInterface $header
     * @return $this
     */
    public function setHeader(BlockHeaderInterface $header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * Calculate the merkle root of this block
     *
     * @return string
     * @throws \Exception
     */
    public function getMerkleRoot()
    {
        $root = new MerkleRoot($this);
        return $root->calculateHash();
    }

    /**
     * Return the array of transactions from this block
     *
     * @return array
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * Set an array of transactions from this block
     *
     * @param array $array
     * @return $this
     */
    public function setTransactions(array $array)
    {
        $this->transactions = $array;
        return $this;
    }
}
