<?php

namespace Afk11\Bitcoin\Math;

use Mdanter\Ecc\MathAdapterInterface;

/**
 * Debug helper class to trace all calls to math functions along with the provided params and result.
 *
 * @author thibaud
 *
 */
class DebugDecorator implements MathAdapterInterface
{

    private $adapter;

    private $writer;

    public function __construct(MathAdapterInterface $adapter, $callback = null)
    {
        $this->adapter = $adapter;
        $this->writer = $callback ?: function ($message) {
            echo $message;
        };
    }

    /**
     *
     * @param string $message
     */
    private function writeln($message)
    {
        call_user_func($this->writer, $message . PHP_EOL);
    }

    /**
     *
     * @param string $message
     */
    private function write($message)
    {
        call_user_func($this->writer, $message);
    }

    /**
     *
     * @param string $func
     * @param array $args
     * @return mixed
     */
    private function call($func, $args)
    {
        $strArgs = array_map(function ($arg) {
            return var_export($this->adapter->toString($arg), true);
        }, $args);

        if (strpos($func, '::')) {
            list(, $func) = explode('::', $func);
        }

        $res = call_user_func_array([ $this->adapter, $func ], $args);

        $this->writeln($func . '(' . implode(', ', $strArgs) . ') => ' . var_export($this->adapter->toString($res), true));

        return $res;
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::cmp()
     */
    public function cmp($first, $other)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::mod()
     */
    public function mod($number, $modulus)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::add()
     */
    public function add($augend, $addend)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::sub()
     */
    public function sub($minuend, $subtrahend)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::mul()
     */
    public function mul($multiplier, $multiplicand)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::div()
     */
    public function div($dividend, $divisor)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::pow()
     */
    public function pow($base, $exponent)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::rand()
     */
    public function rand($n)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::bitwiseAnd()
     */
    public function bitwiseAnd($first, $other)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::toString()
     */
    public function toString($value)
    {
        return $this->adapter->toString($value);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::hexDec()
     */
    public function hexDec($hexString)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::decHex()
     */
    public function decHex($decString)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::powmod()
     */
    public function powmod($base, $exponent, $modulus)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::isPrime()
     */
    public function isPrime($n)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::nextPrime()
     */
    public function nextPrime($currentPrime)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::inverseMod()
     */
    public function inverseMod($a, $m)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::jacobi()
     */
    public function jacobi($a, $p)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::intToString()
     */
    public function intToString($x)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::stringToInt()
     */
    public function stringToInt($s)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::digestInteger()
     */
    public function digestInteger($m)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::gcd2()
     */
    public function gcd2($a, $m)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }
    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::leftShift()
     */
    public function leftShift($a, $b)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapterInterface::rightShift()
     */
    public function rightShift($a, $b)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }
}
