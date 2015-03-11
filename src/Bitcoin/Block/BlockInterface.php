<?php

namespace Afk11\Bitcoin\Block;

use Afk11\Bitcoin\SerializableInterface;
use Afk11\Bitcoin\Transaction\TransactionCollection;

interface BlockInterface extends SerializableInterface
{
    const CURRENT_VERSION = 2;
    const MAX_BLOCK_SIZE = 1000000;

    /**
     * @return BlockHeaderInterface
     */
    public function getHeader();

    /**
     * @return mixed
     */
    public function getMerkleRoot();

    /**
     * @return TransactionCollection
     */
    public function getTransactions();
}
