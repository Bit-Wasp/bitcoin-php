<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Block\BlockSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Transaction\TransactionCollection;
use BitWasp\Bitcoin\Bloom\BloomFilter;

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
     * @param BloomFilter $filter
     * @return FilteredBlock
     */
    public function filter(BloomFilter $filter)
    {
        $vMatch = [];
        $vHashes = [];

        $txns = $this->getTransactions();
        for ($i = 0, $txCount = count($txns); $i < $txCount; $i++) {
            $tx = $txns->getTransaction($i);
            $vMatch[] = $filter->isRelevantAndUpdate($tx);
            $vHashes[] = $tx->getTxHash();
        }

        return new FilteredBlock(
            $this->getHeader(),
            PartialMerkleTree::create($txCount, $vHashes, $vMatch)
        );
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Buffertools\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        $serializer = new BlockSerializer(
            $this->math,
            new BlockHeaderSerializer(),
            new TransactionSerializer()
        );

        return $serializer->serialize($this);
    }
}
