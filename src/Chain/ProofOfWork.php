<?php


namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Bitcoin\Math\Math;

class ProofOfWork
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var Difficulty
     */
    private $difficulty;

    /**
     * @param Math $math
     * @param Difficulty $difficulty
     * @param int|string $networkDifficulty
     */
    public function __construct(Math $math, Difficulty $difficulty, $networkDifficulty)
    {
        $this->math = $math;
        $this->difficulty = $difficulty;
        $this->networkDifficulty = $networkDifficulty;
    }

    /**
     * @return int|string
     */
    public function limit()
    {
        return $this->difficulty->getTarget($this->difficulty->lowestBits());
    }

    /**
     * @param BlockHeaderInterface $header
     * @return bool
     * @throws \Exception
     */
    public function checkHeader(BlockHeaderInterface $header)
    {
        $math = $this->math;

        $target = $this->difficulty->getTarget($header->getBits());
        if ($math->cmp($target, 0) == 0 || $math->cmp($target, $this->limit()) > 0) {
            throw new \Exception('nBits below minimum work');
        }

        $hashInt = $math->hexDec($header->getBlockHash());
        if ($math->cmp($hashInt, $target) > 0) {
            throw new \Exception("Hash doesn't match nBits");
        }

        return true;
    }
}
