<?php

namespace Afk11\Bitcoin\Key;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\Serializer\Key\PublicKey\HexPublicKeySerializer;
use Mdanter\Ecc\GeneratorPoint;
use Mdanter\Ecc\PointInterface;

class PublicKeyFactory
{
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
    public static function fromPoint(PointInterface $point, $compressed = false, Math $math = null, GeneratorPoint $generator = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        $publicKey = new PublicKey($point, $compressed);
        return $publicKey;
    }

    /**
     * @param $hex
     * @return PrivateKey
     */
    public static function fromHex($hex, Math $math = null, GeneratorPoint $generator = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        $hexSerializer = new HexPublicKeySerializer($math, $generator);
        $publicKey = $hexSerializer->parse($hex);

        return $publicKey;
    }
}
