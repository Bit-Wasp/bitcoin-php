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
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Chain\DifficultyInterface::lowestBits()
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
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Chain\DifficultyInterface::getMaxTarget()
     */
    public function getMaxTarget()
    {
        $bits   = $this->lowestBits();
        $target = $this->math->getCompact($bits);

        return $target;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Chain\DifficultyInterface::getTarget()
     */
    public function getTarget(Buffer $bits)
    {
        $target = $this->math->getCompact($bits);
        return $target;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Chain\DifficultyInterface::getTargetHash()
     */
    public function getTargetHash(Buffer $bits)
    {
        $target = $this->getTarget($bits);
        return Buffer::hex($this->math->decHex($target), 32); // let buffer pad it
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Chain\DifficultyInterface::getDifficulty()
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
