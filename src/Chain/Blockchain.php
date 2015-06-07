<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Utxo\UtxoSet;

class Blockchain
{
    /**
     * @var BlockInterface[]
     */
    private $blocks = [];

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
     * @param Difficulty $difficulty
     * @param UtxoSet $utxoSet
     */
    public function __construct(Difficulty $difficulty, UtxoSet $utxoSet)
    {
        $this->utxoset = $utxoSet;
        $this->difficulty = $difficulty;
        $this->chainDiff = $this->difficulty->getDifficulty($this->difficulty->lowestBits());
    }

    /**
     * @return UtxoSet
     */
    public function getUtxoSet()
    {
        return $this->utxoset;
    }

    /**
     * @return float|string
     */
    public function getChainDifficulty()
    {
        return $this->chainDiff;
    }

    /**
     * @return int
     */
    public function size()
    {
        return count($this->blocks);
    }

    /**
     * @param BlockInterface $block
     */
    public function add(BlockInterface $block)
    {
        $this->blocks[] = $block;

        foreach ($block->getTransactions()->getTransactions() as $tx) {
            $this->utxoset->add($tx);
        }

        if ($this->size() % 2016 === 0) {
            $this->chainDiff = $this->difficulty->getDifficulty($block->getHeader()->getBits());
        }
    }

    /**
     * @param BlockInterface $givenBlock
     * @return bool|int
     */
    public function lastAncestor(BlockInterface $givenBlock)
    {
        $prevHash = $givenBlock->getHeader()->getPrevBlock();

        for ($i = $this->size() - 1; $i > 0; $i++) {
            if ($prevHash === $this->blocks[$i]->getHeader()->getBlockHash()) {
                return $i;
            }
        }

        return false;
    }
}
