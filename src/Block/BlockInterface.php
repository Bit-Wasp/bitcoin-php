<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\SerializableInterface;
use BitWasp\Bitcoin\Transaction\TransactionCollection;

interface BlockInterface extends SerializableInterface
{
    const CURRENT_VERSION = 2;
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
     * @return string
     */
    public function getMerkleRoot();

    /**
     * Return the TransactionCollection from the block.
     *
     * @return TransactionCollection
     */
    public function getTransactions();
}
