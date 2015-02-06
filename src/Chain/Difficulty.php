<?php

namespace Bitcoin\Chain;

use Bitcoin\Math\Math;
use Bitcoin\Buffer;

/**
 * Class Difficulty
 * @package Bitcoin\Chain
 * @author Thomas Kerin
 */
class Difficulty implements DifficultyInterface
{
    const MAX_TARGET = '1d00ffff';

    /**
     * @var \Bitcoin\Buffer
     */
    protected $lowestBits;

    /**
     * @var \Mdanter\Ecc\MathAdapter
     */
    protected $math;

    /**
     * @param Math $math
     * @param Buffer $lowestBits
     */
    public function __construct(Math $math, Buffer $lowestBits = null)
    {
        $this->math = $math;
        $this->lowestBits = $lowestBits;
    }

    /**
     * Return the lowest 'bits' - for difficulty 1.
     *
     * @return \Bitcoin\Buffer
     */
    public function lowestBits()
    {
        if (is_null($this->lowestBits)) {
            // Todo - from container?
            return Buffer::hex(self::MAX_TARGET);
        }

        return $this->lowestBits;
    }

    /**
     * Get Max target - that of difficulty 1.
     *
     * @return int|string
     */
    public function getMaxTarget()
    {
        $bits   = $this->lowestBits();
        $target = $this->math->getCompact($bits);

        return $target;
    }

    /**
     * Get the target from a compact int.
     *
     * @param \Bitcoin\Buffer $bits
     * @return int|string
     */
    public function getTarget(Buffer $bits)
    {
        $target = $this->math->getCompact($bits);

        return $target;
    }

    /**
     * Get target hash from bits.
     *
     * @param \Bitcoin\Buffer $bits
     * @return int|string
     */
    public function getTargetHash(Buffer $bits)
    {
        $target = $this->getTarget($bits);
        $target = str_pad($this->math->decHex($target), 64, '0', STR_PAD_LEFT);
        return $target;
    }

    /**
     * Get the difficulty of the supplied bits relative to the lowest target.
     *
     * @param \Bitcoin\Buffer $bits
     * @return float|number
     */
    public function getDifficulty(Buffer $bits)
    {
        $compact = $this->math->unpackCompact($bits);
        $lowest  = $this->math->unpackCompact($this->lowestBits());

        $diff = log($lowest['mul'] / (float)$compact['mul']) + ($lowest['exp'] - $compact['exp'])*log(pow(2, 8));
        $diff = pow(M_E, $diff);

        return $diff;
    }
}
