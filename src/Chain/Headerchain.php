<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Bitcoin\Exceptions\BlockPowError;
use BitWasp\Bitcoin\Exceptions\BlockPrevNotFound;
use BitWasp\Bitcoin\Math\Math;

class Headerchain
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var HeaderStorage
     */
    private $headers;

    /**
     * @var BlockHeaderInterface
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
     * @param Math $math
     * @param BlockHeaderInterface $genesis
     * @param HeaderStorage $blocks
     * @param BlockIndex $index
     */
    public function __construct(Math $math, BlockHeaderInterface $genesis, HeaderStorage $blocks, BlockIndex $index)
    {
        $this->math = $math;
        $this->index = $index;
        $this->genesis = $genesis;
        $this->headers = $blocks;

        try {
            $this->index()->height()->height();
        } catch (\Exception $e) {
            $this->headers->save($genesis);
            $this->index->saveGenesis($genesis);
        }

        $initBlock = $this->chainTip();
        $this->difficulty = new Difficulty($math, $initBlock->getBits());
        $this->chainDiff = $this->difficulty->getDifficulty($initBlock->getBits());
        $this->pow = new ProofOfWork($this->math, $this->difficulty, $this->chainDiff);
    }

    /**
     * @return BlockHeaderInterface
     */
    public function chainTip()
    {
        return $this->headers()->fetch($this->currentBlockHash());
    }

    /**
     * @return BlockIndex
     */
    public function index()
    {
        return $this->index;
    }

    /**
     * @return HeaderStorage
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * @throws \Exception
     */
    public function utxos()
    {
        throw new \Exception('Utxo set not available at the moment');
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
     * @param BlockHeaderInterface $block
     * @return $this
     */
    private function storeBlock(BlockHeaderInterface $block)
    {
        $this->headers()->save($block);
        $this->index()->save($block);
        return $this;
    }

    /**
     * @return $this
     */
    private function updateProofOfWork()
    {
        if ($this->math->cmp(0, $this->math->mod($this->currentHeight(), 2016)) == 0) {
            $this->chainDiff = $this->difficulty->getDifficulty($this->chainTip()->getBits());
            $this->pow = new ProofOfWork($this->math, $this->difficulty, $this->chainDiff);
        }
        return $this;
    }

    /**
     * Add the block to the cache and index, commiting the utxos also.
     * Will fail if it doesn't elongate the current chain
     *
     * @param BlockHeaderInterface $header
     * @throws BlockPrevNotFound
     */
    public function add(BlockHeaderInterface $header)
    {
        if ($this->chainTip()->getBlockHash() !== $header->getPrevBlock()) {
            throw new BlockPrevNotFound('Block does not elongate the current chain');
        }

        $this
            ->storeBlock($header)
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
        $blocks = $this->headers();
        $index = $this->index();

        /** @var \BitWasp\Bitcoin\Block\BlockHeaderInterface[] $chainHeaders */
        $chainHeaders = [];
        $chainHeight = $index->height()->height();
        for ($i = $ancestorHeight; $i < $chainHeight; $i++) {
            $chainHeaders[] = $blocks->fetch($index->hash()->fetch($i));
        }

        /** @var \BitWasp\Bitcoin\Block\BlockHeaderInterface[] $forkHeaders */
        $forkHeaders = [];
        foreach ($forkBlockHashes as $hash) {
            $forkHeaders[] = $blocks->fetch($hash);
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
     * @param BlockHeaderInterface $header
     * @return bool
     */
    public function processOrphan(BlockHeaderInterface $header)
    {
        // While an orphan is not immediately useful, we may be tracking a fork.
        // Save each to the cache, and assess for any link in the chain that we might wish to follow.
        $prevHash = $header->getBlockHash();
        $blocks = $this->headers();
        $index = $this->index();

        $this->headers()->save($header);

        // Determine fork block hashes, and more importantly, the ancestor height
        $forkBlockHashes = [];
        while ($blocks->contains($prevHash)) {
            $forkBlockHashes[] = $prevHash;
            if ($index->height()->contains($prevHash)) {
                $ancestorHeight = $index->height()->fetch($prevHash);
                break;
            }

            $prevHash = $blocks->fetch($prevHash)->getPrevBlock();
        }

        // Only process forks which actually have a valid ancestor in the BlockIndex
        if (isset($ancestorHeight)) {
            return $this->processFork($ancestorHeight, array_reverse($forkBlockHashes));
        }

        return false;
    }

    /**
     * Process a block against the given state of the chain.
     * @param BlockHeaderInterface $header
     * @return bool
     */
    public function process(BlockHeaderInterface $header)
    {
        // Ignore the genesis block
        $hash = $header->getBlockHash();
        if ($hash === $this->genesis->getBlockHash()) {
            return true;
        }

        if ($this->index()->height()->contains($hash)) {
            return true;
        }

        try {
            // Attempt to add it to the chain
            $this->add($header);
            $this->pow->checkHeader($header);
            $result = true;
        } catch (BlockPrevNotFound $e) {
            // If it fails because it doesn't elongate the chain, process it as an orphan.
            // Result will be determined
            $result = $this->processOrphan($header);
        } catch (BlockPowError $e) {
            $result = false;
            // Invalid block.
        }

        return $result;
    }
}
