<?php

namespace Afk11\Bitcoin;

use Afk11\Bitcoin\Math\Math;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\MathAdapterInterface;
use Mdanter\Ecc\GeneratorPoint;

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
     * @return Math
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
        return self::$generator ?: EccFactory::getSecgCurves(self::getMath())->generator256k1();
    }

    /**
     * @param GeneratorPoint $generator
     */
    public static function setGenerator(GeneratorPoint $generator)
    {
        self::$generator = $generator;
    }

    /**
     * @param NetworkInterface $network
     */
    public static function setNetwork(NetworkInterface $network)
    {
        self::$network = $network;
    }

    /**
     * @return Network
     */
    public static function getNetwork()
    {
        if (is_null(self::$network)) {
            $network = new Network('00', '05', '80');
            $network
                ->setHDPubByte('0488b21e')
                ->setHDPrivByte('0488ade4');
            self::$network = $network;
        }

        return self::$network;
    }
}
