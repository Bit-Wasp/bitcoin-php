<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\Network;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Exceptions\MerkleTreeEmpty;
use Pleo\Merkle\FixedSizeTree;

class MerkleRoot
{
    /**
     * @var BlockInterface
     */
    protected $block;

    /**
     * @var Math
     */
    protected $math;

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
     * @param Math $math
     * @param BlockInterface $block
     */
    public function __construct(Math $math, BlockInterface $block)
    {
        $this->math = $math;
        $this->block = $block;
    }

    /**
     * @return string
     */
    private function getLastHash()
    {
        $hash = $this->lastHash;
        return $hash;
    }

    /**
     * Set the last hash. Should only be set by calculateHash()
     *
     * @param string $lastHash
     */
    private function setLastHash($lastHash)
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
        $transactions = $this->block->getTransactions();
        $txCount = $transactions->count();
        $hashFxn = function ($value) {
            return hash('sha256', hash('sha256', $value, true), true);
        };

        if ($txCount == 0) {
            // TODO: Probably necessary. Should always have a coinbase at least.
            throw new MerkleTreeEmpty('Cannot compute Merkle root of an empty tree');
        }

        if ($txCount == 1) {
            $buffer = new Buffer($hashFxn($transactions->getTransaction(0)->getBinary()), 32);

        } else {
            // Create a fixed size Merkle Tree
            $tree = new FixedSizeTree($txCount + ($txCount % 2), $hashFxn);

            // Compute hash of each transaction
            $last = '';
            foreach ($transactions->getTransactions() as $i => $transaction) {
                $last = $transaction->getBinary();
                $tree->set($i, $last);
            }

            // Check if we need to repeat the last hash (odd number of transactions)
            if (!$this->math->isEven($txCount)) {
                $tree->set($txCount, $last);
            }

            $buffer = new Buffer($tree->hash());
        }

        $hash = new Parser();
        $hash = $hash
            ->writeBytes(32, $buffer, true)
            ->getBuffer()
            ->getHex();

        $this->setLastHash($hash);
        return $this->getLastHash();
    }
}
