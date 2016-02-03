<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Block\BlockSerializer;
use BitWasp\Bitcoin\Collection\Transaction\TransactionCollection;
use BitWasp\Bitcoin\Bloom\BloomFilter;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\CommonTrait\FunctionAliasArrayAccess;

class Block extends Serializable implements BlockInterface
{
    use FunctionAliasArrayAccess;

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
     * @var MerkleRoot
     */
    private $merkleRoot;

    /**
     * @param Math $math
     * @param BlockHeaderInterface $header
     * @param TransactionCollection $transactions
     */
    public function __construct(Math $math, BlockHeaderInterface $header, TransactionCollection $transactions)
    {
        $this->math = $math;
        $this->header = $header;
        $this->transactions = $transactions;
        $this
            ->initFunctionAlias('header', 'getHeader')
            ->initFunctionAlias('merkleRoot', 'getMerkleRoot')
            ->initFunctionAlias('tx', 'getTransactions');
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
        if (null === $this->merkleRoot) {
            $this->merkleRoot = new MerkleRoot($this->math, $this->getTransactions());
        }

        return $this->merkleRoot->calculateHash();
    }

    /**
     * @see \BitWasp\Bitcoin\Block\BlockInterface::getTransactions()
     * @return TransactionCollection
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param int $i
     * @return \BitWasp\Bitcoin\Transaction\TransactionInterface
     */
    public function getTransaction($i)
    {
        return $this->transactions[$i];
    }

    /**
     * @param BloomFilter $filter
     * @return FilteredBlock
     */
    public function filter(BloomFilter $filter)
    {
        $vMatch = [];
        $vHashes = [];
        foreach ($this->getTransactions() as $tx) {
            $vMatch[] = $filter->isRelevantAndUpdate($tx);
            $vHashes[] = $tx->getTxHash();
        }

        return new FilteredBlock(
            $this->getHeader(),
            PartialMerkleTree::create(count($this->getTransactions()), $vHashes, $vMatch)
        );
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Buffertools\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        return (new BlockSerializer($this->math, new BlockHeaderSerializer(), new TransactionSerializer()))->serialize($this);
    }
}
