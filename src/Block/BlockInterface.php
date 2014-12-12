<?php

namespace Bitcoin\Block;

/**
 * Interface BlockInterface
 * @package Bitcoin\Block
 */
interface BlockInterface
{
    const CURRENT_VERSION = 2;
    const MAX_BLOCK_SIZE = 1000000;
    public function getHeader();
    public function getMerkleRoot();
    public function getTransactions();
}
