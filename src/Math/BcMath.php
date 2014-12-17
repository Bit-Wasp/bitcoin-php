<?php

namespace Bitcoin\Math;

/**
 * *********************************************************************
 * Copyright (C) 2012 Matyas Danter
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES
 * OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 * ***********************************************************************
 */

class BcMath implements MathAdapter
{

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::cmp()
     */
    public function cmp($first, $other)
    {
        return bccomp($first, $other);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::mod()
     */
    public function mod($number, $modulus)
    {
        $res = bcmod($number, $modulus);

        if (bccomp(0, $res) > 0) {
            $res = bcadd($modulus, $res);
        }

        return $res;
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::add()
     */
    public function add($augend, $addend)
    {
        return bcadd($augend, $addend);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::sub()
     */
    public function sub($minuend, $subtrahend)
    {
        return bcsub($minuend, $subtrahend);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::mul()
     */
    public function mul($multiplier, $multiplicand)
    {
        return bcmul($multiplier, $multiplicand);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::div()
     */
    public function div($dividend, $divisor)
    {
        return bcdiv($dividend, $divisor);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::pow()
     */
    public function pow($base, $exponent)
    {
        return bcpow($base, $exponent);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::rand()
     */
    public function rand($n)
    {
        return BcMathUtils::bcrand($n);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::bitwiseAnd()
     */
    public function bitwiseAnd($first, $other)
    {
        return BcMathUtils::bcand($first, $other);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::toString()
     */
    public function toString($value)
    {
        return $value;
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::hexDec()
     */
    public function hexDec($hex)
    {
        return BcMathUtils::bchexdec($hex);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::decHex()
     */
    public function decHex($dec)
    {
        return BcMathUtils::bcdechex($dec);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::powmod()
     */
    public function powmod($base, $exponent, $modulus)
    {
        if ($exponent < 0) {
            throw new \InvalidArgumentException("Negative exponents ($exponent) not allowed.");
        }

        return bcpowmod($base, $exponent, $modulus);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::isPrime()
     */
    public function isPrime($n)
    {
        $t = 40;
        $k = 0;
        $m = $this->sub($n, 1);

        while ($this->mod($m, 2) == 0) {
            $k = $this->add($k, 1);
            $m = $this->div($m, 2);
        }

        for ($i = 0; $i < $t; $i ++) {
            $a = BcMathUtils::bcrand(1, bcsub($n, 1));
            $b0 = $this->powmod($a, $m, $n);

            if ($b0 != 1 && $b0 != $this->sub($n, 1)) {
                $j = 1;

                while ($j <= $k - 1 && $b0 != $this->sub($n, 1)) {
                    $b0 = $this->powmod($b0, 2, $n);

                    if ($b0 == 1) {
                        return false;
                    }

                    $j ++;
                }

                if ($b0 != bcsub($n, 1)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::nextPrime()
     */
    public function nextPrime($starting_value)
    {
        if (bccomp($starting_value, 2) == - 1) {
            return 2;
        }

        $result = BcMathUtils::bcor(bcadd($starting_value, 1), 1);

        while (! $this->isPrime($result)) {
            $result = bcadd($result, 2);
        }

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::inverseMod()
     */
    public function inverseMod($a, $m)
    {
        while (bccomp($a, 0) == - 1) {
            $a = bcadd($m, $a);
        }

        while (bccomp($m, $a) == - 1) {
            $a = bcmod($a, $m);
        }

        $c = $a;
        $d = $m;
        $uc = 1;
        $vc = 0;
        $ud = 0;
        $vd = 1;

        while (bccomp($c, 0) != 0) {
            $temp1 = $c;
            $q = bcdiv($d, $c, 0);

            $c = bcmod($d, $c);
            $d = $temp1;

            $temp2 = $uc;
            $temp3 = $vc;
            $uc = bcsub($ud, bcmul($q, $uc));
            $vc = bcsub($vd, bcmul($q, $vc));
            $ud = $temp2;
            $vd = $temp3;
        }

        if (bccomp($d, 1) != 0) {
            throw new \RuntimeException("ERROR: $a and $m are NOT relatively prime.");
        }

        $result = bcadd($ud, $m);

        if (bccomp($ud, 0) == 1) {
            $result = $ud;
        }

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::jacobi()
     */
    public function jacobi($a, $n)
    {
        if ($n >= 3 && $n % 2 == 1) {
            $a = $this->mod($a, $n);

            if ($a == 0) {
                return 0;
            }

            if ($a == 1) {
                return 1;
            }

            $a1 = $a;
            $e = 0;

            while ($this->mod($a1, 2) == 0) {
                $a1 = bcdiv($a1, 2);
                $e = bcadd($e, 1);
            }

            if ($this->mod($e, 2) == 0 || $this->mod($n, 8) == 1 || $this->mod($n, 8) == 7) {
                $s = 1;
            } else {
                $s = - 1;
            }

            if ($a1 == 1) {
                return $s;
            }

            if (bcmod($n, 4) == 3 && bcmod($a1, 4) == 3) {
                $s = - $s;
            }

            return bcmul($s, $this->jacobi(bcmod($n, $a1), $a1));
        }

        throw new \RuntimeException('Could not calc Jacobi');
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::intToString()
     */
    public function intToString($x)
    {
        if (bccomp($x, 0) != - 1) {
            if (bccomp($x, 0) == 0) {
                return chr(0);
            }

            $result = "";

            while (bccomp($x, 0) == 1) {
                $q = bcdiv($x, 256, 0);
                $r = bcmod($x, 256);
                $ascii = chr($r);

                $result = $ascii . $result;
                $x = $q;
            }

            return $result;
        }

        throw new \RuntimeException();
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::stringToInt()
     */
    public function stringToInt($s)
    {
         $result = 0;

        for ($c = 0; $c < strlen($s); $c ++) {
            $result = bcadd(bcmul(256, $result), ord($s[$c]));
        }

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::digestInteger()
     */
    public function digestInteger($m)
    {
        return $this->stringToInt(hash('sha1', $this->intToString($m), true));
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::gcd2()
     */
    public function gcd2($a, $b)
    {
        while ($a) {
            $temp = $a;
            $a = bcmod($b, $a);
            $b = $temp;
        }

        return $b;
    }

    public function divQr($dividend, $divisor)
    {
        $div = $this->div($dividend, $divisor);
        // $remainder = n - (n / q) * q
        $remainder = $this->sub($dividend, $this->mul($div, $divisor));
        return array($div, $remainder);
    }
}
