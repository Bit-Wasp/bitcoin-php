<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Buffertools\Buffer;

class Difficulty implements DifficultyInterface
{
    const MAX_TARGET = '1d00ffff';
    
    const DIFF_PRECISION = 12;

    /**
     * @var Buffer
     */
    protected $lowestBits;

    /**
     * @var Math
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
     * @return Buffer
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
     * @param \BitWasp\Buffertools\Buffer $bits
     * @return string
     */
    public function getTarget(Buffer $bits)
    {
        $target = $this->math->getCompact($bits);

        return $target;
    }

    /**
     * Get target hash from bits.
     *
     * @param Buffer $bits
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
     * @param Buffer $bits
     * @return float|number
     */
    public function getDifficulty(Buffer $bits)
    {
        $target = $this->math->getCompact($bits);
        
        $lowest = $this->math->getCompact($this->lowestBits());
        $lowest = $this->math->mul($lowest, $this->math->pow(10, self::DIFF_PRECISION));
        
        $difficulty = str_pad($this->math->div($lowest, $target), self::DIFF_PRECISION + 1, '0', STR_PAD_LEFT);
        
        $intPart = substr($difficulty, 0, 0 - self::DIFF_PRECISION);
        $decPart = substr($difficulty, 0 - self::DIFF_PRECISION, self::DIFF_PRECISION);
        
        return $intPart . '.' . $decPart;
    }
}
