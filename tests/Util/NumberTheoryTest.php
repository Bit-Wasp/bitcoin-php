<?php

namespace Bitcoin\Tests;

use Bitcoin\Bitcoin;
use Mdanter\Ecc\Math\Gmp;
use Mdanter\Ecc\Math\BcMath;
use Mdanter\Ecc\GeneratorPoint;
use Bitcoin\Util\NumberTheory;

class NumberTheoryTest extends \PHPUnit_Framework_TestCase
{

	protected $compression_data;
	protected $sqrt_data;

	/**
	 * @var GeneratorPoint
	 */
	protected $generator;
	protected $theory;
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
		$this->generator = Bitcoin::getGenerator();
        $this->compression_data = json_decode(file_get_contents($file_comp));
        
        $this->sqrt_data = json_decode(file_get_contents($file_sqrt));

    }

	/**
     * @expectedException \Bitcoin\Exceptions\SquareRootException
	 */
	public function testSqrtDataWithNoRootsBcMath()
	{
		$this->math = new BcMath();
		$this->theory = new \Bitcoin\Util\NumberTheory($this->math);
		
		foreach($this->sqrt_data->no_root as $r)
		{
			$this->theory->squareRootModP($r->a, $r->p);	
		}
	}
	/**
	 * @expectedException \Bitcoin\Exceptions\SquareRootException
	 */
	public function testSqrtDataWithNoRootsGmp()
	{
		$this->math = new Gmp();
		$this->theory = new \Bitcoin\Util\NumberTheory($this->math);
		
		foreach($this->sqrt_data->no_root as $r)
		{
			$this->theory->squareRootModP($r->a, $r->p);	
		}
	}
	
	public function testSqrtDataWithRootsGmp()
	{
		$this->math = new Gmp();
		$this->theory = new \Bitcoin\Util\NumberTheory($this->math);
		
		foreach($this->sqrt_data->has_root as $r)
		{
			$root1 = $this->theory->squareRootModP($r->a, $r->p);
			$root2 = $this->math->sub($r->p, $root1);
			$this->assertTrue(in_array($root1, $r->res));
			$this->assertTrue(in_array($root1, $r->res));
			
		}
	}
	/**
     * This runs into an error..
     */
    public function testSqrtDataWithRootsBcMath()
	{
		$this->math = new BcMath();
		$this->theory = new \Bitcoin\Util\NumberTheory($this->math);
		
		foreach($this->sqrt_data->has_root as $r)
		{
			$root1 = $this->theory->squareRootModP($r->a, $r->p);
			$root2 = $this->math->sub($r->p, $root1);
			$this->assertTrue(in_array($root1, $r->res));
			$this->assertTrue(in_array($root1, $r->res));
		}
	}
	
	
	public function testBcmathCompressionConsistency()
	{
		$this->math = new BcMath();
		$this->theory = new \Bitcoin\Util\NumberTheory($this->math);
		$this->_doCompressionConsistence($this->theory);
		
	}
	public function testGmpCompressionConsistency()
	{
		$this->math = new Gmp();
		$this->theory = new \Bitcoin\Util\NumberTheory($this->math);
		$this->_doCompressionConsistence($this->theory);
	}
	
	public function _doCompressionConsistence($theory)
	{
		
		foreach($this->compression_data as $o)
		{
			// Try and regenerate the y coordinate from the parity byte
			// '04' . $x_coordinate . determined y coordinate should equal $o->decompressed
			// Tests squareRootModP which touches most functions in NumberTheory
			$y_byte = substr($o->compressed, 0, 2);
			$x_coordinate = substr($o->compressed, 2);
			
			$x = $this->math->hexDec($x_coordinate);

			// x^3 
			$x3 = $this->math->powmod( $x, 3, $this->generator->getCurve()->getPrime() );
			
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

			// y1 = other root = p - y0
			$y1 = gmp_strval($this->math->sub($this->generator->getCurve()->getPrime(), $y0), 16);

			if($y_byte == '02')
			{
				$y_coordinate = ($this->math->mod($y0, 2) == '0')
					? gmp_strval(gmp_init($y0,10),16)
					: gmp_strval(gmp_sub($this->generator->getCurve()->getPrime(), $y0), 16);
			}
			else
			{
				$y_coordinate = ($this->math->mod($y0, 2) == '0')
					? gmp_strval(gmp_sub($this->generator->getCurve()->getPrime(), $y0), 16)
					: gmp_strval(gmp_init($y0,10),16);
			}
			$y_coordinate = str_pad($y_coordinate,64,'0',STR_PAD_LEFT);
	
			// Successfully regenerated uncompressed ECDSA key from the x coordinate and the parity byte.
			$this->assertTrue('04'.$x_coordinate.$y_coordinate == $o->decompressed);
		}
	}
	

	public function testGmpModFunction()
	{
		$math = new Gmp();
		
		// $o->compressed, $o->decompressed public key.
		// Check that we can compress a key properly (tests $math->mod())
		foreach($this->compression_data as $o)
		{	
			$prefix = substr($o->decompressed, 0, 2); // will be 04.
			
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
	
	public function testBcmathModFunction()
	{
		$math = new BcMath();
		
		// $o->compressed, $o->decompressed public key.
		// Check that we can compress a key properly (tests $math->mod())
		foreach($this->compression_data as $o)
		{
			$prefix = substr($o->decompressed, 1, 2);
			
			// hex encoded (X,Y) coordinate of ECDSA public key.
			$x = substr($o->decompressed, 2, 64);
			$y = substr($o->decompressed, 66, 64);

			// y % 2 == 0       - true: y is even(02) / false: y is odd(03)
			$mod = $math->mod($math->hexDec($y), 2);
			
			// Prefix x coordinate with 02/03 depending on parity.
			$compressed = '0'.(($mod==0) ? '2' : '3').$x;

			$this->assertTrue($compressed === $o->compressed);
			
		}
	
	}
}
