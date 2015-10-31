<?php

namespace BitWasp\Bitcoin\Math;

use Mdanter\Ecc\Math\MathAdapterInterface;

class BinaryMath
{
    /**
     * @var MathAdapterInterface
     */
    private $math;

    /**
     * @param MathAdapterInterface $math
     */
    public function __construct(MathAdapterInterface $math)
    {
        $this->math = $math;
    }

    /**
     * @param $bitSize
     * @return int|string
     */
    private function fixSize($bitSize)
    {
        return $this->math->sub($bitSize, 1);
    }

    /**
     * @param $integer
     * @param $bitSize
     * @return bool
     */
    public function isNegative($integer, $bitSize)
    {
        return $this->math->cmp($this->math->rightShift($integer, $this->fixSize($bitSize)), '1') === 0;
    }

    /**
     * @param $integer
     * @param $bitSize
     * @return int|string
     */
    public function makeNegative($integer, $bitSize)
    {
        return $this->math->bitwiseXor($this->math->leftShift(1, $this->fixSize($bitSize)), $integer);
    }

    /**
     * @param $integer
     * @param $bitSize
     * @return int|string
     */
    public function getTwosComplement($integer, $bitSize)
    {
        return $this->math->add($this->math->pow(2, $bitSize), $integer);
    }
}
