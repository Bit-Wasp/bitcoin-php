<?php

namespace Bitcoin\Math;

use Bitcoin\Util\Buffer;

/**
 * Class Math
 * @package Bitcoin
 */
class Math implements \Mdanter\Ecc\MathAdapterInterface
{
    protected $math;

    public function __construct(\Mdanter\Ecc\MathAdapterInterface $math = null)
    {
        if (is_null($math)) {
            $math = \Mdanter\Ecc\EccFactory::getAdapter();
        }

        $this->math = $math;
    }

    public function decHex($dec)
    {
        $hex = $this->math->decHex($dec);

        if (strlen($hex) % 2 != '0') {
            $hex = '0' . $hex;
        }
        return $hex;
    }

    public function gcd2($a, $b)
    {
        return $this->math->gcd2($a, $b);
    }

    public function isEven($i)
    {
        return $this->math->mod($i, 2) == '0';
    }

    public function nextPrime($starting_value)
    {
        return $this->math->nextPrime($starting_value);
    }

    public function toString($value)
    {
        return $this->math->toString($value);
    }

    public function bitwiseAnd($a, $b)
    {
        return $this->math->bitwiseAnd($a, $b);
    }

    public function hexDec($dec)
    {
        return $this->math->hexDec($dec);
    }

    public function add($augend, $addend)
    {
        return $this->math->add($augend, $addend);
    }
    public function sub($a, $b)
    {
        return $this->math->sub($a, $b);
    }

    public function mul($a, $b)
    {
        return $this->math->mul($a, $b);
    }

    public function div($a, $b)
    {
        return $this->math->div($a, $b);
    }

    public function pow($a, $b)
    {
        return $this->math->pow($a, $b);
    }

    public function powmod($base, $exponent, $modulus)
    {
        return $this->math->powmod($base, $exponent, $modulus);
    }

    public function jacobi($a, $n)
    {
        return $this->math->jacobi($a, $n);
    }

    public function cmp($first, $other)
    {
        return $this->math->cmp($first, $other);
    }

    public function mod($number, $modulus)
    {
        return $this->math->mod($number, $modulus);
    }

    public function inverseMod($a, $n)
    {
        return $this->math->inverseMod($a, $n);
    }

    public function intToString($x)
    {
        return $this->math->intToString($x);
    }

    public function stringToInt($x)
    {
        return $this->math->stringToInt($x);
    }

    public function digestInteger($m)
    {
        return $this->math->digestInteger($m);
    }

    public function isPrime($n)
    {
        return $this->math->isPrime($n);
    }

    public function rand($n)
    {
        return $this->math->rand($n);
    }

    /**
     * Similar to gmp_div_qr, return a tuple containing the
     * result and the remainder
     *
     * @param $dividend
     * @param $divisor
     * @return array
     */
    public function divQr($dividend, $divisor)
    {
        // $div = n / q
        $div = $this->math->div($dividend, $divisor);
        // $remainder = n - (n / q) * q
        $remainder = $this->math->sub($dividend, $this->math->mul($div, $divisor));
        return array($div, $remainder);
    }

    public function unpackCompact(Buffer $bits)
    {
        $bitStr = $bits->serialize();

        // Unpack and decode
        $sci    = array_map(
            function ($value) {
                return $this->math->hexDec($value);
            },
            unpack('H2exp/H6mul', $bitStr)
        );
        return $sci;
    }

    public function mulCompact($int, $pow)
    {
        return $this->math->mul(
            $int,
            $this->math->pow(
                2,
                $this->math->mul(
                    8,
                    $this->math->sub(
                        $pow,
                        3
                    )
                )
            )
        );
    }

    public function getCompact(Buffer $bits)
    {
        $compact = $this->unpackCompact($bits);
        $int = $this->mulCompact($compact['mul'], $compact['exp']);
        return $int;
    }
}
