<?php

namespace Afk11\Bitcoin\Key;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Crypto\Random\Random;
use Afk11\Bitcoin\Exceptions\InvalidPrivateKey;
use Afk11\Bitcoin\NetworkInterface;
use Afk11\Bitcoin\Serializer\Key\PrivateKey\HexPrivateKeySerializer;
use Afk11\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;

class PrivateKeyFactory
{
    /**
     * @param bool $compressed
     * @return PrivateKey
     */
    public static function generate($compressed = true)
    {
        $secret = self::generateSecret();
        $privateKey = new PrivateKey(Bitcoin::getMath(), Bitcoin::getGenerator(), $secret, $compressed);
        return $privateKey;
    }

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

    public static function fromInt($int)
    {
        if (!PrivateKey::isValidKey($int)) {
            throw new InvalidPrivateKey('Invalid private key');
        }
    }

    /**
     * @param $wif
     * @return PrivateKey
     */
    public static function fromWif($wif)
    {
        $math = Bitcoin::getMath();
        $generator = Bitcoin::getGenerator();

        $wifSerializer = new WifPrivateKeySerializer($math, new HexPrivateKeySerializer($math, $generator));
        $privateKey = $wifSerializer->parse($wif);
        return $privateKey;
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param NetworkInterface $network
     * @return string
     */
    public static function toWif(PrivateKeyInterface $privateKey, NetworkInterface $network = null)
    {
        $math = Bitcoin::getMath();
        $generator = Bitcoin::getGenerator();
        $network ?: Bitcoin::getNetwork();

        $wifSerializer = new WifPrivateKeySerializer($math, new HexPrivateKeySerializer($math, $generator));
        $wif = $wifSerializer->serialize($network, $privateKey);
        return $wif;
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @return string
     */
    public static function toHex(PrivateKeyInterface $privateKey)
    {
        $math = Bitcoin::getMath();
        $generator = Bitcoin::getGenerator();
        $hexSerializer = new HexPrivateKeySerializer($math, $generator);

        $hex = $hexSerializer->serialize($privateKey);
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
        $hexSerializer = new HexPrivateKeySerializer($math, $generator);

        $privateKey = $hexSerializer->parse($hex);
        return $privateKey;
    }
}
