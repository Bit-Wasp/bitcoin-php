<?php

namespace Afk11\Bitcoin\Key;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Serializer\Key\PublicKey\HexPublicKeySerializer;

class PublicKeyFactory
{

    public static function fromPrivateKey(PrivateKeyInterface $privateKey)
    {
        return $privateKey->getPublicKey();
    }

    /**
     * @param PrivateKeyInterface $publicKey
     * @return string
     */
    public static function toHex(PublicKeyInterface $publicKey)
    {
        $math = Bitcoin::getMath();
        $generator = Bitcoin::getGenerator();
        $hexSerializer = new HexPublicKeySerializer($math, $generator);

        $hex = $hexSerializer->serialize($publicKey);
        return $hex;
    }

    /**
     * @param $hex
     * @return PrivateKey
     */
    public static function fromHex($hex)
    {
        $math = Bitcoin::getMath();
        $generator = Bitcoin::getGenerator();
        $hexSerializer = new HexPublicKeySerializer($math, $generator);

        $publicKey = $hexSerializer->parse($hex);
        return $publicKey;
    }
}
