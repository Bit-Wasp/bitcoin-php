<?php

namespace Bitcoin\Util;

use Bitcoin\Exceptions\SquareRootException;
use Mdanter\Ecc\MathAdapter;

    /***********************************************************************
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
     ************************************************************************/

    /**
     * Implementation of some number theoretic algorithms
     *
     * @author Matyas Danter
     */

/**
 * Rewritten to take a MathAdaptor to handle different environments. Has
 * some desireble functions for public key compression/recovery.
 *
 * @author Thomas Kerin
 */

class NumberTheory
{
    protected $adapter;

    public function __construct(MathAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function polynomialReduceMod($poly, $polymod, $p)
    {
        $count_polymod = count($polymod);
        if (end($polymod) == 1 && $count_polymod > 1) {
            while (count($poly) >= $count_polymod) {
                if (end($poly) != 0) {
                    for ($i = 2; $i < $count_polymod + 1; $i++) {
                        $poly[count($poly) - $i] =
                            $this->adapter->mod(
                                $this->adapter->sub(
                                    $poly[count($poly) - $i],
                                    $this->adapter->mul(
                                        end($poly),
                                        $polymod[$count_polymod - $i]
                                    )
                                ),
                                $p
                            );
                    }
                }

                $poly = array_slice($poly, 0, count($poly) - 1);
            }

            return $poly;
        }
    }

    public function polynomialMultiplyMod($m1, $m2, $polymod, $p)
    {
        $prod = array();
        $cm1 = count($m1);
        $cm2 = count($m2);

        for ($i = 0; $i < $cm1; $i++) {
            for ($j = 0; $j < $cm2; $j++) {
                $index = $i + $j;
                if (!isset($prod[$index])) {
                    $prod[$index] = 0;
                }
                $prod[$index] =
                    $this->adapter->mod(
                        $this->adapter->add(
                            $prod[$index],
                            $this->adapter->mul(
                                $m1[$i],
                                $m2[$j]
                            )
                        ),
                        $p
                    );

            }
        }

        return $this->polynomialReduceMod($prod, $polymod, $p);
    }

    public function polynomialPowMod($base, $exponent, $polymod, $p)
    {
        if ($this->adapter->cmp($exponent, $p) < 0) {
            if ($this->adapter->cmp($exponent, 0) == 0) {
                return 1;
            }

            $G = $base;
            $k = $exponent;

            if ($this->adapter->cmp($this->adapter->mod($k, 2), 1) == 0) {
                $s = $G;
            } else {
                $s = array(1);
            }

            while ($this->adapter->cmp($k, 1) > 0) {
                $k = $this->adapter->div($k, 2);

                $G = $this->polynomialMultiplyMod($G, $G, $polymod, $p);
                if ($this->adapter->mod($k, 2) == 1) {
                    $s = $this->polynomialMultiplyMod($G, $s, $polymod, $p);
                }
            }
            return $s;
        }
    }

    public function squareRootModP($a, $p)
    {
        if (0 <= $a && $a < $p && 1 < $p) {
            if ($a == 0) {
                return 0;
            }

            if ($p == 2) {
                return $a;
            }
            $jac = $this->adapter->jacobi($a, $p);

            if ($jac == -1) {
                throw new SquareRootException($a . " has no square root modulo " . $p);
            }

            if ($this->adapter->mod($p, 4) == 3) {
                return $this->adapter->powmod($a, $this->adapter->div($this->adapter->add($p, 1), 4), $p);
            }

            if ($this->adapter->mod($p, 8) == 5) {
                $d = $this->adapter->powmod($a, $this->adapter->div($this->adapter->sub($p, 1), 4), $p);
                if ($d == 1) {
                    return $this->adapter->powmod($a, $this->adapter->div($this->adapter->add($p, 3), 8), $p);
                }
                if ($d == $p - 1) {
                    return $this->adapter->mod(
                        $this->adapter->mul(
                            $this->adapter->mul(
                                2,
                                $a
                            ),
                            $this->adapter->powmod(
                                $this->adapter->mul(
                                    4,
                                    $a
                                ),
                                $this->adapter->div(
                                    $this->adapter->sub(
                                        $p,
                                        5
                                    ),
                                    8
                                ),
                                $p
                            )
                        ),
                        $p
                    );
                }
                //shouldn't get here
            }

            for ($b = 2; $b < $p; $b++) {
                if ($this->adapter->jacobi(
                    $this->adapter->sub(
                        $this->adapter->mul($b, $b),
                        $this->adapter->mul(4, $a)
                    ),
                    $p
                ) == -1
                ) {
                    $f = array($a, -$b, 1);

                    $ff = $this->polynomialPowMod(
                        array(0, 1),
                        $this->adapter->div(
                            $this->adapter->add(
                                $p,
                                1
                            ),
                            2
                        ),
                        $f,
                        $p
                    );

                    if ($ff[1] == 0) {
                        return $ff[0];
                    }
                    // if we got here no b was found
                }
            }
        }
    }
}
