<?php

namespace Bitcoin\Math;

interface MathAdapter
{
    /**
     * Compares two numbers
     *
     * @param int|string $first
     * @param int|string $other
     * @return int less than 0 if first is less than second, 0 if equal, greater than 0 if greater than.
     */
    function cmp($first, $other);

    /**
     * Returns the remainder of a division
     *
     * @param int|string $number
     * @param int|string $modulus
     * @return int|string
     */
    function mod($number, $modulus);

    /**
     * Adds two numbers
     *
     * @param int|string $augend
     * @param int|string $addend
     * @return int|string
     */
    function add($augend, $addend);

    /**
     * Substract one number from another
     *
     * @param int|string $minuend
     * @param int|string $subtrahend
     * @return int|string
     */
    function sub($minuend, $subtrahend);

    /**
     * Multiplies a number by another.
     *
     * @param int|string $multiplier
     * @param int|string $multiplicand
     * @return int|string
     */
    function mul($multiplier, $multiplicand);

    /**
     * Divides a number by another.
     *
     * @param int|string $dividend
     * @param int|string $divisor
     * @return int|string
     */
    function div($dividend, $divisor);

    /**
     * Raises a number to a power.
     *
     * @param int|string $base The number to raise.
     * @param int|string $exponent The power to raise the number to.
     * @return int|string
     */
    function pow($base, $exponent);

    /**
     * Generates a random integer between 0 (inclusive) and $n (inclusive).
     *
     * @param int|string $n Maximum value to return.
     * @return int|string
     */
    function rand($n);

    /**
     * Performs a logical AND between two values.
     *
     * @param int|string $first
     * @param int|string $other
     * @return int|string
     */
    function bitwiseAnd($first, $other);

    /**
     * Returns the string representation of a returned value.
     *
     * @param int|string $value
     */
    function toString($value);

    /**
     * Converts an hexadecimal string to decimal.
     *
     * @param string $hexString
     * @return int|string
     */
    function hexDec($hexString);

    /**
     * Converts a decimal string to hexadecimal.
     *
     * @param int|string $decString
     * @return int|string
     */
    function decHex($decString);

    /**
     * Calculates the modular exponent of a number.
     *
     * @param int|string $base
     * @param int|string $exponent
     * @param int|string $modulus
     */
    function powmod($base, $exponent, $modulus);

    /**
     * Checks whether a number is a prime.
     *
     * @param int|string $n
     * @return boolean
     */
    function isPrime($n);

    /**
     * Gets the next known prime that is greater than a given prime.
     *
     * @param int|string $currentPrime
     * @return int|string
     */
    function nextPrime($currentPrime);

    /**
     *
     * @param int|string $a
     * @param int|string $m
     */
    function inverseMod($a, $m);

    /**
     *
     * @param int|string $a
     * @param int|string $p
     */
    function jacobi($a, $p);

    /**
     * @param int|string $x
     * @return string|null
     */
    function intToString($x);

    /**
     *
     * @param int|string $s
     * @return int|string
     */
    function stringToInt($s);

    /**
     *
     * @param int|string $m
     * @return int|string
     */
    function digestInteger($m);

    /**
     *
     * @param int|string $a
     * @param int|string $m
     * @return int|string
     */
    function gcd2($a, $m);

    function divQr($dividend, $divisor);
}
