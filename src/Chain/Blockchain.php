<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Exceptions\BlockPowError;
use BitWasp\Bitcoin\Exceptions\BlockPrevNotFound;
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
     * @var BlockInterface
     */
    private $genesis;

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
     * @var ProofOfWork
     */
    private $pow;

    /**
     * @var UtxoSet
     */
    private $utxoset;

    /**
     * @param Math $math
     * @param BlockInterface $genesis
     * @param BlockStorage $blocks
     * @param BlockIndex $index
     * @param UtxoSet $utxoSet
     */
    public function __construct(Math $math, BlockInterface $genesis, BlockStorage $blocks, BlockIndex $index, UtxoSet $utxoSet)
    {
        $this->math = $math;
        $this->index = $index;
        $this->genesis = $genesis;
        $this->blocks = $blocks;
        $this->utxoset = $utxoSet;
        $this->difficulty = new Difficulty($math, $genesis->getHeader()->getBits());
        $this->chainDiff = $this->difficulty->getDifficulty($genesis->getHeader()->getBits());
        
        try {
            $this->index()->height()->height();
        } catch (\Exception $e) {
            $this->blocks->saveGenesis($genesis);
            $this->index->saveGenesis($genesis->getHeader());
        }

        $this->chainDiff = $this->difficulty->getDifficulty($this->chainTip()->getHeader()->getBits());
        $this->pow = new ProofOfWork($this->math, $this->difficulty, $this->chainDiff);
    }

    /**
     * @return BlockInterface
     */
    public function chainTip()
    {
        return $this->blocks()->fetch($this->currentBlockHash());
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
        return $this->index()->hash()->height();
    }

    /**
     * @return string
     */
    public function currentBlockHash()
    {
        return $this->index()->hash()->fetch($this->currentHeight());
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
            $this->utxoset->save($tx);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function updateProofOfWork()
    {
        if ($this->math->cmp(0, $this->math->mod($this->currentHeight(), 2016)) == 0) {
            $this->chainDiff = $this->difficulty->getDifficulty($this->chainTip()->getHeader()->getBits());
            $this->pow = new ProofOfWork($this->math, $this->difficulty, $this->chainDiff);
        }
        return $this;
    }

    /**
     * Add the block to the cache and index, commiting the utxos also.
     * Will fail if it doesn't elongate the current chain
     *
     * @param BlockInterface $block
     * @throws BlockPrevNotFound
     */
    public function add(BlockInterface $block)
    {
        if ($this->chainTip()->getHeader()->getBlockHash() !== $block->getHeader()->getPrevBlock()) {
            throw new BlockPrevNotFound('Block does not elongate the current chain');
        }

        $this
            ->storeBlock($block)
            ->storeUtxos($block->getTransactions())
            ->updateProofOfWork();
    }

    /**
     * Given an ancestor height, and a list of hashes in the fork, use this
     * information to compare the work, and if necessary, commit a reorg.
     *
     * @param int $ancestorHeight
     * @param array $forkBlockHashes
     * @return bool
     * @throws \Exception
     */
    public function processFork($ancestorHeight, array $forkBlockHashes)
    {
        $blocks = $this->blocks();
        $index = $this->index();

        /** @var \BitWasp\Bitcoin\Block\BlockHeaderInterface[] $chainHeaders */
        $chainHeaders = [];
        $chainHeight = $index->height()->height();
        for ($i = $ancestorHeight; $i < $chainHeight; $i++) {
            $chainHeaders[] = $blocks->fetch($index->hash()->fetch($i))->getHeader();
        }

        /** @var \BitWasp\Bitcoin\Block\BlockHeaderInterface[] $forkBlocks */
        $forkBlocks = [];
        $forkHeaders = [];
        foreach ($forkBlockHashes as $hash) {
            $block = $blocks->fetch($hash);
            $forkBlocks[] = $block;
            $forkHeaders[] = $block->getHeader();
        }

        // Only recalculate BlockIndex values if fork has greater work than chain blocks since ancestor
        if ($this->difficulty->compareWork($forkHeaders, $chainHeaders) > 0) {
            $index->reorg($ancestorHeight, $forkHeaders);
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
        $prevHash = $header->getBlockHash();
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
        $header = $block->getHeader();
        $hash = $header->getBlockHash();
        if ($hash === $this->genesis->getHeader()->getBlockHash()) {
            return true;
        }

        if ($this->index()->height()->contains($hash)) {
            return true;
        }

        try {
            // Attempt to add it to the chain
            $this->add($block);
            $this->pow->checkHeader($header);
            $result = true;
        } catch (BlockPrevNotFound $e) {
            // If it fails because it doesn't elongate the chain, process it as an orphan.
            // Result will be determined
            $result = $this->processOrphan($block);
        } catch (BlockPowError $e) {
            $result = false;
            // Invalid block.
        }

        return $result;
    }
}
