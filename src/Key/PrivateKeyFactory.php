<?php

namespace Afk11\Bitcoin\Key;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Crypto\Random\Random;
use Afk11\Bitcoin\Exceptions\InvalidPrivateKey;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\Serializer\Key\PrivateKey\HexPrivateKeySerializer;
use Afk11\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;
use Mdanter\Ecc\GeneratorPoint;

class PrivateKeyFactory
{
    /**
     * Generate a buffer containing a valid key
     *
     * @return int|string
     * @throws \Afk11\Bitcoin\Exceptions\RandomBytesFailure
     */
    public static function generateSecret()
    {
        $random = new Random();

        do {
            $buffer = $random->bytes(32);
        } while (! PrivateKey::isValidKey($buffer->serialize('int')));

        return $buffer;
    }

    /**
     * @param $int
     * @param bool $compressed
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return PrivateKey
     */
    public static function fromInt($int, $compressed = false, Math $math = null, GeneratorPoint $generator = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        $privateKey = new PrivateKey($math, $generator, $int, $compressed);
        return $privateKey;
    }

    /**
     * @param bool $compressed
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return PrivateKey
     */
    public static function generate($compressed = false, Math $math = null, GeneratorPoint $generator = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        $secret = self::generateSecret();
        return self::fromInt($secret->serialize('int'), $compressed, $math, $generator);
    }

    /**
     * @param $wif
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return PrivateKey
     * @throws InvalidPrivateKey
     */
    public static function fromWif($wif, Math $math = null, GeneratorPoint $generator = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        $wifSerializer = new WifPrivateKeySerializer($math, new HexPrivateKeySerializer($math, $generator));
        $privateKey = $wifSerializer->parse($wif);

        return $privateKey;
    }

    /**
     * @param $hex
     * @param bool $compressed
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return $this
     */
    public static function fromHex($hex, $compressed = false, Math $math = null, GeneratorPoint $generator = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        $hexSerializer = new HexPrivateKeySerializer($math, $generator);
        $privateKey = $hexSerializer->parse($hex)->setCompressed($compressed);

        return $privateKey;
    }
}
