<?php

namespace Afk11\Bitcoin\Block;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Exceptions\ParserOutOfRange;
use Afk11\Bitcoin\SerializableInterface;
use Afk11\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use Afk11\Bitcoin\Serializer\Block\HexBlockSerializer;
use Afk11\Bitcoin\Serializer\Transaction\TransactionCollectionSerializer;
use Afk11\Bitcoin\Serializer\Transaction\TransactionSerializer;
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

    /**
     * @return string
     */
    public function getBuffer()
    {
        $serializer = new HexBlockSerializer(new HexBlockHeaderSerializer(), new TransactionCollectionSerializer(new TransactionSerializer()));
        $hex = $serializer->serialize($this);
        return $hex;
    }
}
