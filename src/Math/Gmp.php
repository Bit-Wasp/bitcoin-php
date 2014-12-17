<?php

namespace Bitcoin\Math;

use \Bitcoin\Math\MathAdapter;

class Gmp implements MathAdapter
{
    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::cmp()
     */
    public function cmp($first, $other)
    {
        return gmp_cmp($first, $other);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::mod()
     */
    public function mod($number, $modulus)
    {
        $res = gmp_div_r($number, $modulus);

        if (gmp_cmp(0, $res) > 0) {
            $res = gmp_add($modulus, $res);
        }

        return gmp_strval($res);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::add()
     */
    public function add($augend, $addend)
    {
        return gmp_strval(gmp_add($augend, $addend));
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::sub()
     */
    public function sub($minuend, $subtrahend)
    {
        return gmp_strval(gmp_sub($minuend, $subtrahend));
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::mul()
     */
    public function mul($multiplier, $multiplicand)
    {
        return gmp_strval(gmp_mul($multiplier, $multiplicand));
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::div()
     */
    public function div($dividend, $divisor)
    {
        return gmp_strval(gmp_div($dividend, $divisor));
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::pow()
     */
    public function pow($base, $exponent)
    {
        return gmp_strval(gmp_pow($base, $exponent));
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::rand()
     */
    public function rand($n)
    {
        $random = gmp_strval(gmp_random());
        $small_rand = rand();

        while (gmp_cmp($random, $n) > 0) {
            $random = gmp_div($random, $small_rand, GMP_ROUND_ZERO);
        }

        return gmp_strval($random);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::bitwiseAnd()
     */
    public function bitwiseAnd($first, $other)
    {
        return gmp_strval(gmp_and($first, $other));
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::toString()
     */
    public function toString($value)
    {
        if (is_resource($value)) {
            return gmp_strval($value);
        }

        return $value;
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::hexDec()
     */
    public function hexDec($hex)
    {
        return gmp_strval(gmp_init($hex, 16), 10);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::decHex()
     */
    public function decHex($dec)
    {
        $hex = gmp_strval(gmp_init($dec, 10), 16);
        return (strlen($hex) % 2 == '0') ? $hex : '0' . $hex;
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

        return gmp_strval(gmp_powm($base, $exponent, $modulus));
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::isPrime()
     */
    public function isPrime($n)
    {
        $prob = gmp_prob_prime($n);

        if ($prob > 0) {
            return true;
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::nextPrime()
     */
    public function nextPrime($starting_value)
    {
        return gmp_strval(gmp_nextprime($starting_value));
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::inverseMod()
     */
    public function inverseMod($a, $m)
    {
        return gmp_strval(gmp_invert($a, $m));
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::jacobi()
     */
    public function jacobi($a, $n)
    {
        return gmp_strval(gmp_jacobi($a, $n));
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::intToString()
     */
    public function intToString($x)
    {
        $math = $this;

        if (gmp_cmp($x, 0) == 0) {
            return chr(0);
        }

        if ($math->cmp($x, 0) > 0) {
            $result = "";

            while (gmp_cmp($x, 0) > 0) {
                $q = gmp_div($x, 256, 0);
                $r = $math->mod($x, 256);
                $ascii = chr($r);

                $result = $ascii . $result;
                $x = $q;
            }

            return $result;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::stringToInt()
     */
    public function stringToInt($s)
    {
        $math = $this;
        $result = 0;

        for ($c = 0; $c < strlen($s); $c ++) {
            $result = $math->add($math->mul(256, $result), ord($s[$c]));
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
            $a = $this->mod($b, $a);
            $b = $temp;
        }

        return gmp_strval($b);
    }

    public function divQr($dividend, $divisor)
    {
        $div = $this->div($dividend, $divisor);
        // $remainder = n - (n / q) * q
        $remainder = $this->sub($dividend, $this->mul($div, $divisor));
        return array($div, $remainder);
    }
}
