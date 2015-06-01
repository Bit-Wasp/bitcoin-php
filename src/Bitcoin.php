<?php

namespace BitWasp\Bitcoin;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Primitives\GeneratorPoint;

/**
 * Class Bitcoin
 * @package Bitcoin
 */
class Bitcoin
{
    /**
     * @var NetworkInterface
     */
    private static $network;

    /**
     * @return Math
     */
    public static function getMath()
    {
        return new Math();
    }

    /**
     * Load the generator to be used throughout
     */
    public static function getGenerator()
    {
        return EccFactory::getSecgCurves(self::getMath())->generator256k1();
    }

    /**
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return EcAdapterInterface
     */
    public static function getEcAdapter(Math $math = null, GeneratorPoint $generator = null)
    {
        return EcAdapterFactory::getAdapter(
            ($math ?: self::getMath()),
            ($generator ?: self::getGenerator())
        );
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
