<?php

namespace Bitcoin\Block;

use Pleo\Merkle\FixedSizeTree;
use Bitcoin\Util\Math;

/**
 * Class MerkleRoot
 * @package Bitcoin\Block
 * @author Thomas Kerin
 */
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
     * Instantiate the class when given a block
     *
     * @param BlockInterface $block
     */
    public function __construct(BlockInterface $block)
    {
        $this->block = $block;
        return $this;
    }

    /**
     * Return the block used to construct this root
     *
     * @return BlockInterface
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * Set a block to build a root from
     *
     * @param BlockInterface $block
     * @return $this
     */
    public function setBlock(BlockInterface $block)
    {
        $this->block = $block;
        return $this;
    }

    /**
     * Return the closure which is used to hash this block
     * Safely defaults to a callable for SHA256d.
     *
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
     * Set a callable function to be used for hashing the merkle tree
     *
     * @param callable $hashFxn
     * @return $this
     */
    public function setHashFxn(callable $hashFxn)
    {
        $this->hashFxn = $hashFxn;
        return $this;
    }

    /**
     * Return the last hash to be calculated.
     *
     * @return string
     */
    public function getLastHash()
    {
        return $this->lastHash;
    }

    /**
     * Set the last hash. Should only be set by calculateHash()
     *
     * @param string $lastHash
     */
    public function setLastHash($lastHash)
    {
        $this->lastHash = $lastHash;
    }

    /**
     * Calculate the hash from the transactions in this block.
     *
     * @return string
     * @throws \Exception
     */
    public function calculateHash()
    {
        $hashFxn      = $this->getHashFxn();
        $transactions = $this->block->getTransactions();
        $txCount      = count($transactions);

        if ($txCount == 0) {
            // TODO: Probably necessary. Should always have a coinbase at least.
            throw new \Exception('Cannot calculate the hash of a block with no transactions');
        }

        // Create a fixed size Merkle Tree
        $tree = new FixedSizeTree($txCount + (count($txCount) % 2), $hashFxn);

        $lastHash = null;
        // Compute hash of each transaction
        foreach ($transactions as $i => $transaction) {
            // Set value of a leaf of the merkle tree
            $lastHash = $hashFxn($transaction->serialize());
            $tree->set($i, $lastHash);
        }

        // Check if we need to repeat the last hash (odd number of transactions)
        if (Math::mod($txCount, 2) !== 0) {
            $tree->set($txCount, $lastHash);
        }

        // Store the last hash for later.
        $this->setLastHash($tree->hash());

        return $this->getLastHash();
    }
}
