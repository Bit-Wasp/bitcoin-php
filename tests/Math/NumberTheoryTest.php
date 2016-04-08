<?php

namespace BitWasp\Bitcoin\Tests\Math;

use BitWasp\Bitcoin\Tests\AbstractTestCase;
use Mdanter\Ecc\Primitives\GeneratorPoint;
use Mdanter\Ecc\Math\NumberTheory;

class NumberTheoryTest extends AbstractTestCase
{

    protected $compression_data;
    protected $sqrt_data;

    /**
     * @var GeneratorPoint
     */
    protected $generator;
    protected $theory;

    /**
     * @var \BitWasp\Bitcoin\Math\Math
     */
    protected $math;

    protected function setUp()
    {
        // file containing a json array of {compressed=>'', decompressed=>''} values
        // of compressed and uncompressed ECDSA public keys (testing secp256k1 curve)
        $file_comp = __DIR__ . '/../Data/compression.json';

        if (! file_exists($file_comp)) {
            $this->fail('Key compression input data not found');
        }

        $file_sqrt = __DIR__ . '/../Data/square_root_mod_p.json';
        if (! file_exists($file_sqrt)) {
            $this->fail('Square root input data not found');
        }
        $this->math = $this->safeMath();
        $this->generator = $this->safeGenerator();
        $this->compression_data = json_decode(file_get_contents($file_comp));

        $this->sqrt_data = json_decode(file_get_contents($file_sqrt));
    }

    /**
     * @expectedException \LogicException
     */
    public function testSqrtDataWithNoRoots()
    {
        $theory = $this->math->getNumberTheory();

        foreach ($this->sqrt_data->no_root as $r) {
            $theory->squareRootModP($r->a, $r->p);
        }
    }

    public function testSqrtDataWithRoots()
    {
        $theory = $this->math->getNumberTheory();

        foreach ($this->sqrt_data->has_root as $r) {
            $root1 = $theory->squareRootModP($r->a, $r->p);
            $this->assertTrue(in_array($root1, $r->res));
            $this->assertTrue(in_array($root1, $r->res));
        }
    }

    public function testGmpCompressionConsistency()
    {
        $theory = $this->math->getNumberTheory();
        $this->_doCompressionConsistence($theory);
    }

    /**
     * @param NumberTheory $theory
     */
    public function _doCompressionConsistence(NumberTheory $theory)
    {

        foreach ($this->compression_data as $o) {
        // Try and regenerate the y coordinate from the parity byte
            // '04' . $x_coordinate . determined y coordinate should equal $o->decompressed
            // Tests squareRootModP which touches most functions in NumberTheory
            $y_byte = substr($o->compressed, 0, 2);
            $x_coordinate = substr($o->compressed, 2);

            $x = $this->math->hexDec($x_coordinate);

            // x^3
            $x3 = $this->math->powmod($x, 3, $this->generator->getCurve()->getPrime());

            // y^2
            $y2 = $this->math->add(
                $x3,
                $this->generator->getCurve()->getB()
            );

            // y0 = sqrt(y^2)
            $y0 = $theory->squareRootModP(
                $y2,
                $this->generator->getCurve()->getPrime()
            );

            if ($y_byte == '02') {
                $y_coordinate = ($this->math->mod($y0, 2) == '0')
                    ? gmp_strval(gmp_init($y0, 10), 16)
                    : gmp_strval(gmp_sub($this->generator->getCurve()->getPrime(), $y0), 16);
            } else {
                $y_coordinate = ($this->math->mod($y0, 2) == '0')
                    ? gmp_strval(gmp_sub($this->generator->getCurve()->getPrime(), $y0), 16)
                    : gmp_strval(gmp_init($y0, 10), 16);
            }
            $y_coordinate = str_pad($y_coordinate, 64, '0', STR_PAD_LEFT);

            // Successfully regenerated uncompressed ECDSA key from the x coordinate and the parity byte.
            $this->assertTrue('04'.$x_coordinate.$y_coordinate == $o->decompressed);
        }
    }

    /**
     *
     */
    public function testGmpModFunction()
    {
        $math = $this->math;

        // $o->compressed, $o->decompressed public key.
        // Check that we can compress a key properly (tests $math->mod())
        foreach ($this->compression_data as $o) {
            // hex encoded (X,Y) coordinate of ECDSA public key.
            $x = substr($o->decompressed, 2, 64);
            $y = substr($o->decompressed, 66, 64);

            // y % 2 == 0       - true: y is even(02) / false: y is odd(03)
            $mod = $math->mod($math->hexDec($y), 2);
            $compressed = '0'.(($mod==0) ? '2' : '3').$x;

            // Check that the mod function reported the parity for the y value.
            $this->assertTrue($compressed === $o->compressed);
        }
    }
}
