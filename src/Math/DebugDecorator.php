<?php

namespace Bitcoin\Math;

/**
 * Debug helper class to trace all calls to math functions along with the provided params and result.
 *
 * @author thibaud
 *
 */
class DebugDecorator implements MathAdapter
{

    private $adapter;

    private $writer;

    public function __construct(MathAdapter $adapter, $callback = null)
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
     * @see \Mdanter\Ecc\MathAdapter::cmp()
     */
    public function cmp($first, $other)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::mod()
     */
    public function mod($number, $modulus)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::add()
     */
    public function add($augend, $addend)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::sub()
     */
    public function sub($minuend, $subtrahend)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::mul()
     */
    public function mul($multiplier, $multiplicand)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::div()
     */
    public function div($dividend, $divisor)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::pow()
     */
    public function pow($base, $exponent)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::rand()
     */
    public function rand($n)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::bitwiseAnd()
     */
    public function bitwiseAnd($first, $other)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::toString()
     */
    public function toString($value)
    {
        return $this->adapter->toString($value);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::hexDec()
     */
    public function hexDec($hexString)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::decHex()
     */
    public function decHex($decString)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::powmod()
     */
    public function powmod($base, $exponent, $modulus)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::isPrime()
     */
    public function isPrime($n)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::nextPrime()
     */
    public function nextPrime($currentPrime)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::inverseMod()
     */
    public function inverseMod($a, $m)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::jacobi()
     */
    public function jacobi($a, $p)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::intToString()
     */
    public function intToString($x)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::stringToInt()
     */
    public function stringToInt($s)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::digestInteger()
     */
    public function digestInteger($m)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }

    /**
     * (non-PHPdoc)
     * @see \Mdanter\Ecc\MathAdapter::gcd2()
     */
    public function gcd2($a, $m)
    {
        $func = __METHOD__;
        $args = func_get_args();

        return call_user_func([ $this, 'call' ], $func, $args);
    }
}
