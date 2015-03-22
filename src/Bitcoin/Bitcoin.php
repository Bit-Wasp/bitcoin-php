<?php

namespace BitWasp\Bitcoin;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
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
     * @var MathAdapterInterface
     */
    private static $math;

    /**
     * @var GeneratorPoint
     */
    private static $generator;

    /**
     * @var NetworkInterface
     */
    private static $network;

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
    public static function getDefaultNetwork()
    {
        return NetworkFactory::bitcoin();
    }

    /**
     * @return Network
     */
    public static function getNetwork()
    {
        if (is_null(self::$network)) {
            self::$network = NetworkFactory::bitcoin();
        }

        return self::$network;
    }
}
