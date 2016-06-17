<?php

namespace BitWasp\Bitcoin\Math;

use Mdanter\Ecc\Math\GmpMathInterface;

class BinaryMath
{
    /**
     * @var GmpMathInterface
     */
    private $math;

    /**
     * @param GmpMathInterface $math
     */
    public function __construct(GmpMathInterface $math)
    {
        $this->math = $math;
    }

    /**
     * @param int $bitSize
     * @return int
     */
    private function fixSize($bitSize)
    {
        return $bitSize - 1;
    }

    /**
     * @param \GMP $integer
     * @param int $bitSize
     * @return bool
     */
    public function isNegative(\GMP $integer, $bitSize)
    {
        return $this->math->cmp($this->math->rightShift($integer, $this->fixSize($bitSize)), gmp_init(1)) === 0;
    }

    /**
     * @param \GMP $integer
     * @param int $bitSize
     * @return \GMP
     */
    public function makeNegative(\GMP $integer, $bitSize)
    {
        return $this->math->bitwiseXor($this->math->leftShift(gmp_init(1), $this->fixSize($bitSize)), $integer);
    }

    /**
     * @param \GMP $integer
     * @param int $bitSize
     * @return \GMP
     */
    public function getTwosComplement(\GMP $integer, $bitSize)
    {
        return $this->math->add($this->math->pow(gmp_init(2), $bitSize), $integer);
    }
}
