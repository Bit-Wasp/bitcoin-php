<?php

namespace Bitcoin\Block;

use Pleo\Merkle\FixedSizeTree;
use Bitcoin\Util\Math;
use Bitcoin\Block\BlockInterface;

class MerkleRoot
{
    /**
     * @var BlockInterface
     */
    protected $block;

    /**
     * @var callable
     */
    protected $hashFxn;

    /**
     * @var string
     */
    protected $lastHash;

    /**
     * @param BlockInterface $block
     */
    public function __construct(BlockInterface $block)
    {
        $this->block = $block;
        return $this;
    }

    /**
     * @return BlockInterface
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * @param BlockInterface $block
     */
    public function setBlock(BlockInterface $block)
    {
        $this->block = $block;
        return $this;
    }

    /**
     * @return callable
     */
    public function getHashFxn()
    {
        if ($this->hashFxn == null) {
            return \Bitcoin\Network::getHashFunction();
        }
        return $this->hashFxn;
    }

    /**
     * @param callable $hashFxn
     */
    public function setHashFxn(callable $hashFxn)
    {
        $this->hashFxn = $hashFxn;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastHash()
    {
        return $this->lastHash;
    }

    /**
     * @param string $lastHash
     */
    public function setLastHash($lastHash)
    {
        $this->lastHash = $lastHash;
    }

    /**
     *
     */
    public function calculateHash()
    {
        $transactions = $this->block->getTransactions();
        $txCount      = count($transactions);

        if ($txCount == 0) {
            throw new \Exception('Cannot calculate the hash of a block with no transactions');
        }

        // Create a fixed size Merkle Tree
        $tree = new FixedSizeTree($txCount + (count($txCount) % 2), $this->getHashFxn());

        $lastHash = null;
        // Compute hash of each transaction
        foreach ($transactions as $i => $transaction) {
            $lastHash = $transaction->serialize();
            // Set value of a leaf of the merkle tree

            $tree->set($i, $lastHash);
        }
        // Check if we need to repeat the last hash (odd number of transactions)
        if (Math::mod($txCount, 2) !== 0) {
            $tree->set($txCount, $lastHash);
        }

        $this->setLastHash($tree->hash());

        return $this->getLastHash();
    }
}
