<?php

namespace Afk11\Bitcoin\Block;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Exceptions\ParserOutOfRange;
use Afk11\Bitcoin\Transaction\TransactionCollection;
use Afk11\Bitcoin\Transaction\TransactionFactory;

class Block implements BlockInterface
{
    /**
     * @var Math
     */
    protected $math;

    /**
     * @var BlockHeader
     */
    protected $header;

    /**
     * @var TransactionCollection
     */
    protected $transactions;

    /**
     * @param Parser $parser
     * @return Block
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser &$parser)
    {
        try {
            $header = new BlockHeader();
            $header->fromParser($parser);
            $this->setHeader($header);
            $this->getTransactions()->addTransactions(
                $parser->getArray(
                    function () use (&$parser) {
                        $transaction = TransactionFactory::fromParser($parser);
                        return $transaction;
                    }
                )
            );
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full block header from parser');
        }

        return $this;
    }

    /**
     * @param null $type
     * @return string
     */
    public function serialize($type = null)
    {
        $header = new Buffer($this->getHeader()->serialize());
        $parser = new Parser($header);
        $parser->writeArray($this->getTransactions()->getTransactions());
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
        $math = Bitcoin::getMath();
        $parser = new Parser($buffer);

        $block  = new self($math);
        $block->fromParser($parser);
        return $block;
    }

    /**
     * Instantiate class
     * @param Math $math
     */
    public function __construct(Math $math)
    {
        $this->header = new BlockHeader();
        $this->math = $math;
        $this->transactions = new TransactionCollection();
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
        $root = new MerkleRoot($this->math, $this);
        return $root->calculateHash();
    }

    /**
     * Return the array of transactions from this block
     *
     * @return TransactionCollection
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
    public function setTransactions(TransactionCollection $collection)
    {
        $this->transactions = $collection;
        return $this;
    }
}
