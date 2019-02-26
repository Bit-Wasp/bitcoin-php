<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter as PhpEcc;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter as Secp256k1;
use BitWasp\Bitcoin\Math\Math;
use Composer\Semver\Semver;
use Mdanter\Ecc\Primitives\GeneratorPoint;

class EcAdapterFactory
{
    /**
     * @var EcAdapterInterface
     */
    private static $adapter;

    /**
     * @var resource
     */
    private static $context;

    /**
     * @param int|null $flags
     * @return resource
     */
    public static function getSecp256k1Context(int $flags = null)
    {
        if (!extension_loaded('secp256k1')) {
            throw new \RuntimeException('Secp256k1 not installed');
        }

        if (self::$context === null) {
            $context = secp256k1_context_create($flags ?: SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);
            if (null === $context) {
                throw new \RuntimeException("Failed to initialize secp256k1 context");
            }
            self::$context = $context;
        }

        return self::$context;
    }

    /**
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return EcAdapterInterface
     */
    public static function getAdapter(Math $math, GeneratorPoint $generator): EcAdapterInterface
    {
        if (self::$adapter !== null) {
            return self::$adapter;
        }

        if (extension_loaded('secp256k1') && Semver::satisfies(phpversion('secp256k1'), "^0.2.0")) {
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
    public static function getPhpEcc(Math $math, GeneratorPoint $generator): PhpEcc
    {
        return new PhpEcc($math, $generator);
    }

    /**
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return Secp256k1
     */
    public static function getSecp256k1(Math $math, GeneratorPoint $generator): Secp256k1
    {
        return new Secp256k1($math, $generator, self::getSecp256k1Context());
    }
}
