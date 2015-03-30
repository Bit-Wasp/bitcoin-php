<?php

namespace BitWasp\Bitcoin;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
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
     * @var EcAdapterInterface
     */
    private static $ecAdapter;

    /**
     * @return Math
     */
    public static function getMath()
    {
        if (null === self::$math) {
            self::$math = new Math();
        }

        return self::$math;
    }

    /**
     * @param MathAdapterInterface $adapter
     */
    public static function setMath(MathAdapterInterface $adapter)
    {
        self::$math = $adapter;
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
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return EcAdapterInterface
     */
    public static function getEcAdapter(Math $math = null, GeneratorPoint $generator = null)
    {
        return self::$ecAdapter ?: EcAdapterFactory::getAdapter(
            ($math ?: self::getMath()),
            ($generator ?: self::getGenerator())
        );
    }

    /**
     * @param EcAdapterInterface $adapter
     */
    public static function setEcAdapter(EcAdapterInterface $adapter)
    {
        self::$ecAdapter = $adapter;
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
            self::$network = self::getDefaultNetwork();
        }

        return self::$network;
    }

    public static function getDefaultNetwork()
    {
        return NetworkFactory::bitcoin();
    }
}
