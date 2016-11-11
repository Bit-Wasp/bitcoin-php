<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Transaction\TransactionCollection;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Exceptions\MerkleTreeEmpty;
use Pleo\Merkle\FixedSizeTree;

class MerkleRoot
{
    /**
     * @var TransactionCollection
     */
    private $transactions;

    /**
     * @var Math
     */
    private $math;

    /**
     * @var string
     */
    private $lastHash;

    /**
     * Instantiate the class when given a block
     *
     * @param Math $math
     * @param TransactionCollection $txCollection
     */
    public function __construct(Math $math, TransactionCollection $txCollection)
    {
        $this->math = $math;
        $this->transactions = $txCollection;
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
    public function calculateHash($hashFunction = null)
    {
        $hashFxn = $hashFunction ?: function ($value) {
            return hash('sha256', hash('sha256', $value, true), true);
        };

        $txCount = count($this->transactions);

        if ($txCount == 0) {
            // TODO: Probably necessary. Should always have a coinbase at least.
            throw new MerkleTreeEmpty('Cannot compute Merkle root of an empty tree');
        }

        if ($txCount == 1) {
            $buffer = $hashFxn($this->transactions->getTransaction(0)->getBinary());
        } else {
            // Create a fixed size Merkle Tree
            $tree = new FixedSizeTree($txCount + ($txCount % 2), $hashFxn);

            // Compute hash of each transaction
            $last = '';
            foreach ($this->transactions->getTransactions() as $i => $transaction) {
                $last = $transaction->getBinary();
                $tree->set($i, $last);
            }

            // Check if we need to repeat the last hash (odd number of transactions)
            if (!$this->math->isEven($txCount)) {
                $tree->set($txCount, $last);
            }

            $buffer = $tree->hash();
        }

        $hash = new Buffer(Buffertools::flipBytes($buffer));
        $hash = $hash->getHex();

        $this->setLastHash($hash);
        return $this->getLastHash();
    }
}
