<?php

namespace Afk11\Bitcoin\Key;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Exceptions\Base58ChecksumFailure;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\NetworkInterface;
use Afk11\Bitcoin\Crypto\Hash;
use Afk11\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use Afk11\Bitcoin\Serializer\Key\HierarchicalKey\HexExtendedKeySerializer;
use Mdanter\Ecc\GeneratorPoint;

class HierarchicalKeyFactory
{
    /**
     * @param $math
     * @param $generator
     * @param $network
     * @return ExtendedKeySerializer
     */
    public static function getSerializer($math, $generator, $network)
    {
        $extSerializer = new ExtendedKeySerializer($network, new HexExtendedKeySerializer($math, $generator, $network));
        return $extSerializer;
    }

    /**
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return HierarchicalKey
     */
    public static function generateMasterKey(Math $math = null, GeneratorPoint $generator = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        $buffer  = PrivateKeyFactory::create(true, $math, $generator);
        $private = self::fromEntropy($buffer->serialize('hex'));
        return $private;
    }

    /**
     * @param $entropy
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return HierarchicalKey
     */
    public static function fromEntropy($entropy, Math $math = null, GeneratorPoint $generator = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        $hash = Hash::hmac('sha512', pack("H*", $entropy), "Bitcoin seed");
        $depth = 0;
        $parentFingerprint = 0;
        $sequence = 0;
        $chainCode = $math->hexDec(substr($hash, 64, 64));

        $private = PrivateKeyFactory::fromHex(substr($hash, 0, 64), true);

        $key = new HierarchicalKey($math, $generator, $depth, $parentFingerprint, $sequence, $chainCode, $private);

        return $key;
    }

    /**
     * @param $extendedKey
     * @param NetworkInterface $network
     * @return HierarchicalKey
     * @throws Base58ChecksumFailure
     */
    public static function fromExtended($extendedKey, NetworkInterface $network, Math $math = null, GeneratorPoint $generator = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        $extSerializer = self::getSerializer($math, $generator, $network);
        $key = $extSerializer->parse($extendedKey);
        return $key;
    }
}
