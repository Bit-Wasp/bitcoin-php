<?php

namespace Bitcoin;

use Mdanter\Ecc\EccFactory;

/**
 * Class Bitcoin
 * @package Bitcoin
 */
class Bitcoin
{
    private static $math = null;
    private static $generator = null;
    private static $curve = null;

    /**
     * @return \Mdanter\Ecc\MathAdapter
     */
    public static function getMath()
    {
        return self::$math ?: \Mdanter\Ecc\EccFactory::getAdapter();
    }

    /**
     * @param \Mdanter\Ecc\MathAdapter $adapter
     */
    public static function setMath(\Mdanter\Ecc\MathAdapter $adapter)
    {
        self::$math = $adapter;
    }

    /**
     * @return \Mdanter\Ecc\NumberTheory
     */
    public static function getNumberTheory()
    {
        return \Mdanter\Ecc\EccFactory::getNumberTheory(self::getMath());
    }

    /**
     * Load the generator to be used throughout
     */
    public static function getGenerator()
    {
        return self::$generator ?: EccFactory::getSecgCurves(self::getMath())->generator256k1();
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
        return self::$curve ?: EccFactory::getSecgCurves(self::getMath())->curve256k1();
    }

    /**
     * @param \Mdanter\Ecc\CurveFpInterface $curve
     */
    public static function setCurve(\Mdanter\Ecc\CurveFpInterface $curve)
    {
        self::$curve = $curve;
    }
}
