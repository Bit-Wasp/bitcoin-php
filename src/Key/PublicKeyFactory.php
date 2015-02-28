<?php

namespace Afk11\Bitcoin\Key;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\Serializer\Key\PublicKey\HexPublicKeySerializer;
use Mdanter\Ecc\GeneratorPoint;

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
