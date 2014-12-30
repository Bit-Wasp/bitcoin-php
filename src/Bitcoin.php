<?php

namespace Bitcoin;

use Bitcoin\Math\Math;
use Bitcoin\Util\NumberTheory;
use Bitcoin\Network;
use Bitcoin\NetworkInterface;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\MathAdapter;
use Mdanter\Ecc\GeneratorPoint;
use Mdanter\Ecc\CurveFpInterface;

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

    /**
     * @var null|NumberTheory
     */
    private static $numberTheory = null;

    /**
     * @var null|GeneratorPoint
     */
    private static $generator = null;

    /**
     * @var null|NetworkInterface
     */
    private static $network = null;

    /**
     * @var null|CurveFpInterface
     */
    private static $curve = null;

    /**
     * @return MathAdapter
     */
    public static function getMath()
    {
        $math = self::$math ?: new Math();
        return $math;
    }

    /**
     * @param MathAdapter $adapter
     */
    public static function setMath(MathAdapter $adapter)
    {
        self::$math = $adapter;
    }

    /**
     * @return NumberTheory
     */
    public static function getNumberTheory()
    {
        if (is_null(self::$numberTheory)) {
            self::$numberTheory = EccFactory::getNumberTheory(self::getMath());
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
    public static function setGenerator(GeneratorPoint $generator)
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
     * @param CurveFpInterface $curve
     */
    public static function setCurve(CurveFpInterface $curve)
    {
        self::$curve = $curve;
    }

    /**
     * @return Network
     */
    public static function getNetwork()
    {
        if (is_null(self::$network)) {
            $network = new Network('00', '05', '80');
            $network->setHDPubByte('0488B21E');
            $network->setHDPubByte('0488ADE4');
            self::$network = $network;
        }

        return self::$network;
    }
}
