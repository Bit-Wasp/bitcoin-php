<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Math\Math;
use Mdanter\Ecc\Primitives\GeneratorPoint;

class EcAdapterFactory
{
    /**
     * @var EcAdapterInterface
     */
    private static $adapter;

    /**
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return EcAdapterInterface
     */
    public static function getAdapter(Math $math, GeneratorPoint $generator)
    {
        if (self::$adapter !== null) {
            return self::$adapter;
        }

        if (extension_loaded('secp256k1')) {
            self::$adapter = self::getSecp256k1($math, $generator);
        } else {
            self::$adapter = self::getPhpEcc($math, $generator);
        }

        return self::$adapter;
    }

    /**
     * @param EcAdapterInterface $ecAdapter
     */
    public static function setAdapter(EcAdapterInterface $ecAdapter)
    {
        self::$adapter = $ecAdapter;
    }

    /**
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return PhpEcc
     */
    public static function getPhpEcc(Math $math, GeneratorPoint $generator)
    {
        return new PhpEcc($math, $generator);
    }

    private static $context;

    public static function getSecp256k1Context($flags = null)
    {
        if (self::$context == null) {
            self::$context = secp256k1_context_create($flags ?: SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);
        }

        return self::$context;
    }

    /**
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return Secp256k1
     */
    public static function getSecp256k1(Math $math, GeneratorPoint $generator)
    {
        return new Secp256k1($math, $generator, self::getSecp256k1Context());
    }
}
