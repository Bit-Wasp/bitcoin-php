<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Block\HexBlockSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Transaction\TransactionCollection;

class Block extends Serializable implements BlockInterface
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var BlockHeaderInterface
     */
    private $header;

    /**
     * @var TransactionCollection
     */
    private $transactions;

    /**
     * @param Math $math
     * @param BlockHeaderInterface $header
     * @param TransactionCollection $transactions
     */
    public function __construct(Math $math, BlockHeaderInterface $header, TransactionCollection $transactions = null)
    {
        $this->math = $math;
        $this->header = $header;
        $this->transactions = $transactions ?: new TransactionCollection();
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Block\BlockInterface::getHeader()
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Block\BlockInterface::getMerkleRoot()
     * @throws \BitWasp\Bitcoin\Exceptions\MerkleTreeEmpty
     */
    public function getMerkleRoot()
    {
        $root = new MerkleRoot($this->math, $this->getTransactions());
        return $root->calculateHash();
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Block\BlockInterface::getTransactions()
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Buffertools\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        $serializer = new HexBlockSerializer(
            $this->math,
            new HexBlockHeaderSerializer(),
            new TransactionSerializer()
        );

        $hex = $serializer->serialize($this);
        return $hex;
    }
}
