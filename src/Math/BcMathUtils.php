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

/**
 * The bcmath extension in PHP does not implement certain operations for elliptic curve encryption This class implements all neccessary static methods
 */
if (! defined('MAX_BASE')) {
    define('MAX_BASE', 128);
}

class BcMathUtils
{

    /**
     * @param integer $min
     * @param integer $max
     */
    public static function bcrand($min, $max = false)
    {
        if (! $max) {
            $max = $min;
            $min = 0;
        }

        return bcadd(
            bcmul(bcdiv(mt_rand(0, mt_getrandmax()), mt_getrandmax(), strlen($max)), bcsub(bcadd($max, 1), $min)),
            $min
        );
    }

    /**
     * @param string $hex
     */
    public static function bchexdec($hex)
    {
        $len = strlen($hex);
        $dec = '';

        for ($i = 1; $i <= $len; $i ++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }

        return $dec;
    }

    public static function bcdechex($dec)
    {
        $hex = '';
        $positive = $dec < 0 ? false : true;

        while ($dec) {
            $hex .= dechex(abs(bcmod($dec, '16')));
            $dec = bcdiv($dec, '16', 0);
        }

        if ($positive) {
            return strrev($hex);
        }

        for ($i = 0; isset($hex[$i]); $i ++) {
            $hex[$i] = dechex(15 - hexdec($hex[$i]));
        }

        for ($i = 0; isset($hex[$i]) && $hex[$i] == 'f'; $i ++) {
            $hex[$i] = '0';
        }

        if (isset($hex[$i])) {
            $hex[$i] = dechex(hexdec($hex[$i]) + 1);
        }

        return strrev($hex);
    }

    public static function bcand($x, $y)
    {
        return self::internalBitWiseOp($x, $y, 'self::internalAnd');
    }

    // Bitwise OR

    /**
     * @param string $x
     * @param integer $y
     */
    public static function bcor($x, $y)
    {
        return self::internalBitWiseOp($x, $y, 'self::internalOr');
    }

    // Bitwise XOR
    public static function bcxor($x, $y)
    {
        return self::internalBitWiseOp($x, $y, 'self::internalXor');
    }

    // Left shift (<<)
    public static function bcleftshift($num, $shift)
    {
        bcscale(0);
        return bcmul($num, bcpow(2, $shift));
    }

    // Right shift (>>)
    public static function bcrightshift($num, $shift)
    {
        bcscale(0);
        return bcdiv($num, bcpow(2, $shift));
    }

    // // INTERNAL ROUTINES
    // These routines operate on only one byte. They are used to
    // implement internalBitWiseOp.
    private static function internalAnd($x, $y)
    {
        return $x & $y;
    }

    private static function internalOr($x, $y)
    {
        return $x | $y;
    }

    private static function internalXor($x, $y)
    {
        return $x ^ $y;
    }

    // internalBitWiseOp - The majority of the code that implements
    // the bitwise functions bcand, bcor, and bcxor.
    //
    // arguments - $x and $y are the operands (in decimal format),
    // and $op is the name of one of the three
    // internal functions, internalAnd, internalOr, or internalXor.
    //
    //
    // see also - The interfaces to this function: bcand, bcor,
    // and bcxor

    /**
     * @param string $op
     */
    private static function internalBitWiseOp($x, $y, $op)
    {
        $bx = self::bc2bin($x);
        $by = self::bc2bin($y);

        // Pad $bx and $by so that both are the same length.
        self::equalbinpad($bx, $by);

        $ret = '';

        for ($ix = 0; $ix < strlen($bx); $ix ++) {
            $xd = substr($bx, $ix, 1);
            $yd = substr($by, $ix, 1);
            $ret .= call_user_func($op, $xd, $yd);
        }

        return self::bin2bc($ret);
    }

    public static function bc2bin($num)
    {
        return self::dec2base($num, MAX_BASE);
    }

    /**
     * @param string $num
     */
    public static function bin2bc($num)
    {
        return self::base2dec($num, MAX_BASE);
    }

    public static function dec2base($dec, $base, $digits = false)
    {
        if ($base < 2 or $base > 256) {
            throw new \RuntimeException("Invalid Base: " . $base);
        }

        bcscale(0);
        $value = "";

        if (! $digits) {
            $digits = self::digits($base);
        }

        while ($dec > $base - 1) {
            $rest = bcmod($dec, $base);
            $dec = bcdiv($dec, $base);
            $value = $digits[$rest] . $value;
        }

        $value = $digits[intval($dec)] . $value;

        return (string)$value;
    }

    /**
     * @param string $value
     */
    public static function base2dec($value, $base, $digits = false)
    {
        if ($base < 2 or $base > 256) {
            throw new \RuntimeException("Invalid Base: " . $base);
        }

        bcscale(0);

        if ($base < 37) {
            $value = strtolower($value);
        }

        if (! $digits) {
            $digits = self::digits($base);
        }

        $size = strlen($value);
        $dec = "0";

        for ($loop = 0; $loop < $size; $loop ++) {
            $element = strpos($digits, $value[$loop]);
            $power = bcpow($base, $size - $loop - 1);
            $dec = bcadd($dec, bcmul($element, $power));
        }

        return (string)$dec;
    }

    public static function digits($base)
    {
        if ($base > 64) {
            $digits = "";

            for ($loop = 0; $loop < 256; $loop ++) {
                $digits .= chr($loop);
            }
        } else {
            $digits = "0123456789abcdefghijklmnopqrstuvwxyz";
            $digits .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ-_";
        }

        $digits = substr($digits, 0, $base);

        return (string)$digits;
    }

    /**
     * @param string $x
     * @param string $y
     */
    public static function equalbinpad(&$x, &$y)
    {
        $xlen = strlen($x);
        $ylen = strlen($y);

        $length = max($xlen, $ylen);

        self::fixedbinpad($x, $length);
        self::fixedbinpad($y, $length);
    }

    /**
     * @param integer $length
     * @param string $num
     */
    public static function fixedbinpad(&$num, $length)
    {
        $pad = '';

        for ($ii = 0; $ii < $length - strlen($num); $ii ++) {
            $pad .= self::bc2bin('0');
        }

        $num = $pad . $num;
    }
}
