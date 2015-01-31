<?php

namespace Bitcoin;

use Bitcoin\Math\Math;
use Bitcoin\Util\NumberTheory;
use Bitcoin\Network;
use Bitcoin\NetworkInterface;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\MathAdapterInterface;
use Mdanter\Ecc\GeneratorPoint;
use Mdanter\Ecc\CurveFpInterface;

/**
 * Class Bitcoin
 * @package Bitcoin
 */
class Bitcoin
{
    /**
     * @var null|MathAdapterInterface
     */
    private static $math = null;

    /**
     * @var null|GeneratorPoint
     */
    private static $generator = null;

    /**
     * @var null|NetworkInterface
     */
    private static $network = null;

    /**
     * @return MathAdapterInterface
     */
    public static function getMath()
    {
        $math = self::$math ?: new Math();
        return $math;
    }

    /**
     * @param MathAdapterInterface $adapter
     */
    public static function setMath(MathAdapterInterface $adapter)
    {
        self::$math = new Math($adapter);
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
