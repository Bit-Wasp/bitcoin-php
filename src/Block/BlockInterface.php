<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Bloom\BloomFilter;
use BitWasp\Bitcoin\SerializableInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\BufferInterface;

interface BlockInterface extends SerializableInterface
{
    const MAX_BLOCK_SIZE = 1000000;

    /**
     * Get the header of this block.
     *
     * @return BlockHeaderInterface
     */
    public function getHeader();

    /**
     * Calculate the merkle root of the transactions in the block.
     *
     * @return BufferInterface
     */
    public function getMerkleRoot();

    /**
     * Return the block's transactions.
     *
     * @return TransactionInterface[]
     */
    public function getTransactions();

    /**
     * @param int $i
     * @return TransactionInterface
     */
    public function getTransaction($i);

    /**
     * @param BloomFilter $filter
     * @return FilteredBlock
     */
    public function filter(BloomFilter $filter);
}
