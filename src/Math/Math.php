<?php

namespace BitWasp\Bitcoin\Math;

use \Mdanter\Ecc\Math\Gmp;
use Mdanter\Ecc\Util\NumberSize;

class Math extends Gmp
{
    /**
     * @param int|string $first
     * @param int|string $second
     * @return bool
     */
    public function notEq($first, $second)
    {
        return $this->cmp($first, $second) !== 0;
    }

    /**
     * @param $first
     * @param $second
     * @return bool
     */
    public function eq($first, $second)
    {
        return $this->cmp($first, $second) === 0;
    }

    /**
     * @param int|string $first
     * @param int|string $second
     * @return bool
     */
    public function greaterThan($first, $second)
    {
        return $this->cmp($first, $second) > 0;
    }

    /**
     * @param int|string $first
     * @param int|string $second
     * @return bool
     */
    public function greatherThanEq($first, $second)
    {
        return $this->cmp($first, $second) >= 0;
    }

    /**
     * @param int|string $first
     * @param int|string $second
     * @return bool
     */
    public function lessThan($first, $second)
    {
        return $this->cmp($first, $second) > 0;
    }

    /**
     * @param int|string $first
     * @param int|string $second
     * @return bool
     */
    public function lessThanEq($first, $second)
    {
        return $this->cmp($first, $second) >= 0;
    }

    /**
     * @return BinaryMath
     */
    public function getBinaryMath()
    {
        return new BinaryMath($this);
    }

    /**
     * @param $integer
     * @return bool
     */
    public function isEven($integer)
    {
        return $this->cmp($this->mod($integer, 2), 0) === 0;
    }

    /**
     * @param int|string $int
     * @param int|string $otherInt
     * @return string
     */
    public function bitwiseOr($int, $otherInt)
    {
        return gmp_strval(gmp_or(gmp_init($int, 10), gmp_init($otherInt, 10)), 10);
    }

    /**
     * Similar to gmp_div_qr, return a tuple containing the
     * result and the remainder
     *
     * @param $dividend
     * @param integer $divisor
     * @return array
     */
    public function divQr($dividend, $divisor)
    {
        // $div = n / q
        $div = $this->div($dividend, $divisor);
        // $remainder = n - (n / q) * q
        $remainder = $this->sub($dividend, $this->mul($div, $divisor));
        return array($div, $remainder);
    }

    /**
     * @param int|string $compact
     * @param bool|false $isNegative
     * @param bool|false $isOverflow
     * @return int|string
     */
    public function writeCompact($compact, &$isNegative, &$isOverflow)
    {

        $size = $this->rightShift($compact, 24);
        $word = $this->bitwiseAnd($compact, $this->hexDec('007fffff'));
        if ($this->cmp($size, 3) <= 0) {
            $word = $this->rightShift($word, $this->mul(8, $this->sub(3, $size)));
        } else {
            $word = $this->leftShift($word, $this->mul(8, $this->sub($size, 3)));
        }

        // isNegative: $word != 0 && $uint32 & 0x00800000 != 0
        // isOverflow: $word != 0 && (($size > 34) || ($word > 0xff && $size > 33) || ($word > 0xffff && $size  >32))
        $isNegative = (($this->cmp($word, 0) !== 0) && ($this->cmp($this->bitwiseAnd($compact, $this->hexDec('0x00800000')), 0) === 1));
        $isOverflow = $this->cmp($word, 0) !== 0 && (
                ($this->cmp($size, 34) > 0)
                || ($this->cmp($word, 0xff) > 0 && $this->cmp($size, 33) > 0)
                || ($this->cmp($word, 0xffff) > 0 && $this->cmp($size, 32) > 0)
            );

        return $word;
    }

    /**
     * @param int|string $integer
     * @return int|string
     */
    public function getLow64($integer)
    {
        $bits = str_pad($this->baseConvert($integer, 10, 2), 64, '0', STR_PAD_LEFT);
        return $this->baseConvert(substr($bits, 0, 64), 2, 10);
    }

    /**
     * @param int|string $integer
     * @param bool $fNegative
     * @return int|string
     */
    public function parseCompact($integer, $fNegative)
    {
        if (!is_bool($fNegative)) {
            throw new \InvalidArgumentException('CompactInteger::read() - flag must be boolean!');
        }

        $size = (int) NumberSize::bnNumBytes($this, $integer);
        if ($this->cmp($size, 3) <= 0) {
            $compact = $this->leftShift($this->getLow64($integer), $this->mul(8, $this->sub(3, $size)));
        } else {
            $compact = $this->rightShift($integer, $this->mul(8, $this->sub($size, 3)));
            $compact = $this->getLow64($compact);
        }

        if ($this->cmp($this->bitwiseAnd($compact, $this->hexDec('00800000')), 0) > 0) {
            $compact = $this->rightShift($compact, 8);
            $size++;
        }

        $compact = $this->bitwiseOr($compact, $this->leftShift($size, 24));
        if ($fNegative && $this->cmp($this->bitwiseAnd($compact, $this->hexDec('007fffff')), 0) > 0) { /// ?
            $compact = $this->bitwiseOr($compact, $this->hexDec('00800000'));
        }

        return $compact;
    }
}
