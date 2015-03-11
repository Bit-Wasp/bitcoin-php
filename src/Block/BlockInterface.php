<?php

namespace Afk11\Bitcoin\Block;

use Afk11\Bitcoin\Transaction\TransactionCollection;

interface BlockInterface
{
    const CURRENT_VERSION = 2;
    const MAX_BLOCK_SIZE = 1000000;
    public function getHeader();
    public function getMerkleRoot();

    /**
     * @return TransactionCollection
     */
    public function getTransactions();
}
