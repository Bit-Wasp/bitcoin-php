<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Buffertools\Buffer;

interface DifficultyInterface
{

    /**
     * @return Buffer
     */
    public function lowestBits();

    /**
     * Get Max target - that of difficulty 1.
     *
     * @return int|string
     */
    public function getMaxTarget();

    /**
     * Get the target from a compact int.
     *
     * @param \BitWasp\Buffertools\Buffer $bits
     * @return string
     */
    public function getTarget(Buffer $bits);

    /**
     * Get target hash from bits.
     *
     * @param Buffer $bits
     * @return Buffer
     */
    public function getTargetHash(Buffer $bits);

    /**
     * Get the difficulty of the supplied bits relative to the lowest target.
     *
     * @param Buffer $bits
     * @return float
     */
    public function getDifficulty(Buffer $bits);

    /**
     * Get the work associated with a difficulty of $bits
     *
     * @param Buffer $bits
     * @return int|string
     */
    public function getWork(Buffer $bits);
}
