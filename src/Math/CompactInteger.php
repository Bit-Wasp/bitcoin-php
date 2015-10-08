<?php

namespace BitWasp\Bitcoin\Math;

use Mdanter\Ecc\Util\NumberSize;

class CompactInteger
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @param Math $math
     */
    public function __construct(Math $math)
    {
        $this->math = $math;
    }

    /**
     * @param int|string $compact
     * @param bool|false $isNegative
     * @param bool|false $isOverflow
     * @return int|string
     */
    public function set($compact, &$isNegative = false, &$isOverflow = false)
    {
        $math = $this->math;
        $size = $math->rightShift($compact, 24);
        $word = $math->bitwiseAnd($compact, $math->hexDec('007fffff'));
        if ($math->cmp($size, 3) <= 0) {
            $word = $math->rightShift($word, $math->mul(8, $math->sub(3, $size)));
        } else {
            $word = $math->leftShift($word, $math->mul(8, $math->sub($size, 3)));
        }

        // isNegative: $word != 0 && $uint32 & 0x00800000 != 0
        // isOverflow: $word != 0 && (($size > 34) || ($word > 0xff && $size > 33) || ($word > 0xffff && $size  >32))
        $isNegative = (($math->cmp($word, 0) != 0) && ($math->cmp($math->bitwiseAnd($compact, $math->hexDec('0x00800000')), 0) == 1));
        $isOverflow = $math->cmp($word, 0) != 0 && (
            ($math->cmp($size, 34) > 0)
            || ($math->cmp($word, 0xff) > 0 && $math->cmp($size, 33) > 0)
            || ($math->cmp($word, 0xffff) > 0 && $math->cmp($size, 32) > 0)
        );

        return $word;
    }

    /**
     * @param int|string $integer
     * @return int|string
     */
    public function getLow64($integer)
    {
        $bits = str_pad($this->math->baseConvert($integer, 10, 2), 64, '0', STR_PAD_LEFT);
        return $this->math->baseConvert(substr($bits, 0, 64), 2, 10);
    }

    /**
     * @param int|string $integer
     * @param bool $fNegative
     * @return int|string
     */
    public function read($integer, $fNegative)
    {
        if (!is_bool($fNegative)) {
            throw new \InvalidArgumentException('CompactInteger::read() - flag must be boolean!');
        }
        $math = $this->math;
        $size = (int) NumberSize::bnNumBytes($math, $integer);
        if ($math->cmp($size, 3) <= 0) {
            $compact = $math->leftShift($this->getLow64($integer), $math->mul(8, $math->Sub(3, $size)));
        } else {
            $compact = $math->rightShift($integer, $math->mul(8, $math->Sub($size, 3)));
            $compact = $this->getLow64($compact);
        }

        if ($math->cmp($math->bitwiseAnd($compact, $math->hexDec('00800000')), 0) > 0) {
            $compact = $math->rightShift($compact, 8);
            $size++;
        }

        $compact = $math->bitwiseOr($compact, $math->leftShift($size, 24));
        if ($fNegative && $math->cmp($math->bitwiseAnd($compact, $math->hexDec('007fffff')), 0) > 0) { /// ?
            $compact = $math->bitwiseOr($compact, $math->hexDec('00800000'));
        }
        
        return $compact;
    }
}
