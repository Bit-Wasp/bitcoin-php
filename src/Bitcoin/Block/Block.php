<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Block\HexBlockSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionCollectionSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Transaction\TransactionCollection;

class Block extends Serializable implements BlockInterface
{
    /**
     * @var Math
     */
    protected $math;

    /**
     * @var BlockHeaderInterface
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
    }

    /**
     * Return the blocks header
     * TODO: Perhaps these should only be instantiated from a full block?
     *
     * @return BlockHeaderInterface
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
     * @param TransactionCollection $collection
     * @return $this
     */
    public function setTransactions(TransactionCollection $collection)
    {
        $this->transactions = $collection;
        return $this;
    }

    /**
     * @return \BitWasp\Bitcoin\Buffer
     */
    public function getBuffer()
    {
        $serializer = new HexBlockSerializer($this->math, new HexBlockHeaderSerializer(), new TransactionCollectionSerializer(new TransactionSerializer()));
        $hex = $serializer->serialize($this);
        return $hex;
    }
}
