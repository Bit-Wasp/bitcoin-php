<?php

namespace Bitcoin\Tests;
use Bitcoin\Math\MathAdapter;
use Bitcoin\Math\Gmp;
use Bitcoin\Math\BcMath;

class MathTest extends MathTestCases
{

    private $knownPrimes;

    private $startPrime = 31;

    private $primeCount = 10;

    protected function setUp()
    {
        $file = __DIR__ . '/../Data/primes.lst';

        if (! file_exists($file)) {
            $this->fail('Primes not found');
        }

        $lines = file($file);
        if (! $lines) {
            $this->fail('Empty prime file');
        }

        $this->knownPrimes = array_map(function ($i) {
            return intval($i);
        }, $lines);
    }
    /**
    * @dataProvider getAdapters
    */
    public function testStrictIntegerReturnValues(MathAdapter $math)
    {
        $x = 10;
        $y = 4;

        $mod = $math->mod($x, $y);
        $this->assertTrue(is_string($mod) AND ! is_resource($mod));

        $add = $math->add($x, $y);
        $this->assertTrue(is_string($add) AND ! is_resource($add));

        $sub = $math->sub($add, $y);
        $this->assertTrue(is_string($sub) AND ! is_resource($sub));

        $mul = $math->mul($x, $y);
        $this->assertTrue(is_string($mul) AND ! is_resource($mul));

        $div = $math->div($mul, $y);
        $this->assertTrue(is_string($div) AND ! is_resource($div));

        $pow = $math->pow($x, $y);
        $this->assertTrue(is_string($pow) AND ! is_resource($div));

        $rand = $math->rand($x);
        $this->assertTrue(is_string($rand) AND ! is_resource($rand));

        $powmod = $math->powmod($x, $y, $y);
        $this->assertTrue(is_string($powmod) AND ! is_resource($powmod));

        $bitwiseand = $math->bitwiseAnd($x, $y);
        $this->assertTrue(is_string($bitwiseand) AND ! is_resource($bitwiseand));

        $hexdec = $math->decHex($x);
        $this->assertTrue(is_string($hexdec) AND ! is_resource($hexdec));

        $dechex = $math->hexDec($hexdec);
        $this->assertTrue(is_string($dechex) AND ! is_resource($dechex));

    }
    /**
     * @dataProvider getAdapters
     */
    public function testKnownPrimesAreCorrectlyDetected(MathAdapter $math)
    {
        foreach ($this->knownPrimes as $key => $prime) {
            if (trim($prime) == '') {
                user_error('Empty prime number detected from line #' . ($key + 1), E_USER_WARNING);
            }

            $this->assertTrue($math->isPrime($prime), 'Prime "' . $prime . '" is not detected as prime.');
        }
    }

    /**
     * @dataProvider getAdapters
     */
    public function testGetNextPrimes(MathAdapter $math)
    {
        $currentPrime = $math->nextPrime($this->startPrime);

        for ($i = 0; $i < $this->primeCount; $i ++) {
            $currentPrime = $math->nextPrime($currentPrime);
            $this->assertTrue($math->isPrime($currentPrime));

            $this->assertContains($currentPrime, $this->knownPrimes);
        }
    }

    /**
     * @dataProvider getAdapters
     */
    public function testMultInverseModP(MathAdapter $math)
    {
        for ($i = 0; $i < 100; $i ++) {
            $m = rand(20, 10000);

            for ($j = 0; $j < 100; $j ++) {
                $a = rand(1, $m - 1);

                if ($math->gcd2($a, $m) == 1) {
                    $inv = $math->inverseMod($a, $m);
                    $this->assertFalse($inv <= 0 || $inv >= $m || ($a * $inv) % $m != 1);
                }
            }
        }
    }
}
