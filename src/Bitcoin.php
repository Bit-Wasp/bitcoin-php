<?php

namespace Bitcoin;

use Bitcoin\Math\MathAdapter;
use Bitcoin\Math\Gmp;
use Bitcoin\Math\BcMath;
use Bitcoin\Util\NumberTheory;
use Mdanter\Ecc\EccFactory;

/**
 * Class Bitcoin
 * @package Bitcoin
 */
class Bitcoin
{
    /**
     * @var null|MathAdapter
     */
    private static $math = null;

    private static $numberTheory;

    private static $generator = null;

    private static $network = null;

    private static $curve = null;

    /**
     * @return MathAdapter
     */
    public static function getMath()
    {
        if (is_null(self::$math)) {
            if (extension_loaded('gmp')) {
                self::$math = new Gmp();
            } else if (extension_loaded('bcmath')) {
                self::$math = new BcMath();
            }
        }

        return self::$math;
    }

    /**
     * @param MathAdapter $adapter
     */
    public static function setMath(\Bitcoin\Math\MathAdapter $adapter)
    {
        self::$math = $adapter;
    }

    /**
     * @return NumberTheory
     */
    public static function getNumberTheory()
    {
        if (is_null(self::$numberTheory)) {
            self::$numberTheory = new \Bitcoin\Util\NumberTheory(self::getMath());
        }

        return self::$numberTheory;
    }

    /**
     * Load the generator to be used throughout
     */
    public static function getGenerator()
    {
        return self::$generator ?: EccFactory::getSecgCurves()->generator256k1();
    }

    /**
     * @param \Mdanter\Ecc\GeneratorPoint $generator
     */
    public static function setGenerator(\Mdanter\Ecc\GeneratorPoint $generator)
    {
        self::$generator = $generator;
    }

    /**
     * @return \Mdanter\Ecc\CurveFp
     */
    public static function getCurve()
    {
        return self::$curve ?: EccFactory::getSecgCurves()->curve256k1();
    }

    /**
     * @param \Mdanter\Ecc\CurveFpInterface $curve
     */
    public static function setCurve(\Mdanter\Ecc\CurveFpInterface $curve)
    {
        self::$curve = $curve;
    }

    public static function getNetwork()
    {
        if (is_null(self::$network)) {
            $network = new Network('00','05','80');
            $network->setHDPubByte('0488B21E');
            $network->setHDPubByte('0488ADE4');
            self::$network = $network;
        }
    }
}
