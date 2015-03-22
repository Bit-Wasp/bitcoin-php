<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Serializer\Key\PublicKey\HexPublicKeySerializer;
use Mdanter\Ecc\GeneratorPoint;
use Mdanter\Ecc\PointInterface;

class PublicKeyFactory
{
    public static function getSerializer($math, $generator)
    {
        $hexSerializer = new HexPublicKeySerializer($math, $generator);
        return $hexSerializer;
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @return PublicKeyInterface
     */
    public static function fromPrivateKey(PrivateKeyInterface $privateKey)
    {
        return $privateKey->getPublicKey();
    }

    /**
     * @param PointInterface $point
     * @param bool $compressed
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return PublicKey
     */
    public static function fromPoint(
        PointInterface $point,
        $compressed = false,
        Math $math = null,
        GeneratorPoint $generator = null
    ) {
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        $publicKey = new PublicKey($math, $generator, $point, $compressed);
        return $publicKey;
    }

    /**
     * @param $hex
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return PublicKey
     * @throws \Exception
     */
    public static function fromHex($hex, Math $math = null, GeneratorPoint $generator = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        $serializer = self::getSerializer($math, $generator);
        $publicKey = $serializer->parse($hex);

        return $publicKey;
    }
}
