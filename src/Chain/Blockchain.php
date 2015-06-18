<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Transaction\TransactionCollection;
use BitWasp\Bitcoin\Utxo\UtxoSet;

class Blockchain
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var BlockStorage
     */
    private $blocks;

    /**
     * @var BlockIndex
     */
    private $index;

    /**
     * @var Difficulty
     */
    private $difficulty;

    /**
     * @var float|string
     */
    private $chainDiff;

    /**
     * @var UtxoSet
     */
    private $utxoset;

    /**
     * @param Math $math
     * @param Difficulty $difficulty
     * @param BlockInterface $genesis
     * @param BlockStorage $blocks
     * @param BlockIndex $index
     * @param UtxoSet $utxoSet
     */
    public function __construct(Math $math, Difficulty $difficulty, BlockInterface $genesis, BlockStorage $blocks, BlockIndex $index, UtxoSet $utxoSet)
    {
        $this->math = $math;
        $this->index = $index;
        $this->genesis = $genesis;
        $this->blocks = $blocks;
        $this->utxoset = $utxoSet;
        $this->difficulty = $difficulty;
        $this->chainDiff = $this->difficulty->getDifficulty($this->difficulty->lowestBits());

        $this->blocks->save($genesis);
        $this->index->saveGenesis($genesis->getHeader());
    }

    /**
     * @return BlockIndex
     */
    public function index()
    {
        return $this->index;
    }

    /**
     * @return BlockStorage
     */
    public function blocks()
    {
        return $this->blocks;
    }

    /**
     * @return UtxoSet
     */
    public function utxos()
    {
        return $this->utxoset;
    }

    /**
     * @return float|string
     */
    public function difficulty()
    {
        return $this->chainDiff;
    }

    /**
     * @return int
     */
    public function currentHeight()
    {
        $h = $this->index()->hash()->height();
        return $h;
    }

    /**
     * @return string
     */
    public function currentBlockHash()
    {
        $hash = $this->index()->hash()->fetch($this->currentHeight());
        return $hash;
    }

    /**
     * @return BlockInterface
     */
    public function chainTip()
    {
        return $this->blocks()->fetch($this->currentBlockHash());
    }

    /**
     * @param BlockInterface $block
     * @return $this
     */
    private function storeBlock(BlockInterface $block)
    {
        $this->blocks()->save($block);
        $this->index()->save($block->getHeader());
        return $this;
    }

    /**
     * @param TransactionCollection $txs
     * @return $this
     */
    private function storeUtxos(TransactionCollection $txs)
    {
        foreach ($txs->getTransactions() as $tx) {
            $this->utxoset->add($tx);
        }

        return $this;
    }

    /**
     * Add the block to the cache and index, commiting the utxos also.
     * Will fail if it doesn't elongate the current chain
     *
     * @param BlockInterface $block
     */
    public function add(BlockInterface $block)
    {
        if ($this->chainTip()->getHeader()->getBlockHash() !== $block->getHeader()->getPrevBlock()) {
            throw new \RuntimeException('Block does not elongate the current chain');
        }

        $this
            ->storeBlock($block)
            ->storeUtxos($block->getTransactions());

        if (0 === $this->currentHeight() % 2016) {
            $this->chainDiff = $this->difficulty->getDifficulty($block->getHeader()->getBits());
        }
    }

    /**
     * Given an ancestor height, and a list of hashes in the fork, use this
     * information to compare the work, and if necessary, commit a reorg.
     *
     * @param $ancestorHeight
     * @param array $forkBlockHashes
     * @return bool
     * @throws \Exception
     */
    public function processFork($ancestorHeight, array $forkBlockHashes)
    {
        $blocks = $this->blocks();
        $index = $this->index();

        /** @var \BitWasp\Bitcoin\Block\BlockInterface[] $chainBlocks */
        $chainBlocks = [];
        $chainHeight = $index->height()->height();
        for ($i = $ancestorHeight; $i < $chainHeight; $i++){
            $chainBlocks[] = $blocks->fetch($index->hash()->fetch($i));
        }

        /** @var \BitWasp\Bitcoin\Block\BlockInterface[] $forkBlocks */
        $forkBlocks = [];
        foreach ($forkBlockHashes as $hash) {
            $forkBlocks[] = $blocks->fetch($hash);
        }

        // Only recalculate BlockIndex values if fork has greater work than chain blocks since ancestor
        if ($this->difficulty->compareWork($forkBlocks, $chainBlocks) > 0) {
            $index->reorg($ancestorHeight, $forkBlocks);
            return true;
        }

        return false;
    }

    /**
     * Process an orphan. Add the block to the cache, while assessing for forks.
     * May result in non-linear increase of block size.
     *
     * @param BlockInterface $block
     * @return bool
     */
    public function processOrphan(BlockInterface $block)
    {
        // While an orphan is not immediately useful, we may be tracking a fork.
        // Save each to the cache, and assess for any link in the chain that we might wish to follow.

        $header = $block->getHeader();
        $prevHash = $blockHash = $header->getBlockHash();
        $blocks = $this->blocks();
        $index = $this->index();
        $this->blocks()->save($block);

        // Determine fork block hashes, and more importantly, the ancestor height
        $forkBlockHashes = [];
        while ($blocks->contains($prevHash)) {
            $forkBlockHashes[] = $prevHash;
            if ($index->height()->contains($prevHash)) {
                $ancestorHeight = $index->height()->fetch($prevHash);
                break;
            }

            $prevHash = $blocks->fetch($prevHash)->getHeader()->getPrevBlock();
        }

        // Only process forks which actually have a valid ancestor in the BlockIndex
        if (isset($ancestorHeight)) {
            return $this->processFork($ancestorHeight, array_reverse($forkBlockHashes));
        }

        return false;
    }

    /**
     * Process a block against the given state of the chain.
     * @param BlockInterface $block
     * @return bool
     */
    public function process(BlockInterface $block)
    {
        // Ignore the genesis block
        $hash = $block->getHeader()->getBlockHash();
        if ($hash === $this->genesis->getHeader()->getBlockHash()) {
            return true;
        }

        try {
            // Attempt to add it to the chain
            $this->add($block);
            $result = true;
        } catch (\RuntimeException $e) {
            // If it fails because it doesn't elongate the chain, process it as an orphan.
            // Result will be determined
            $result = $this->processOrphan($block);
        }

        return $result;
    }

}
