<?php

namespace Afk11\Bitcoin\Key;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Exceptions\Base58ChecksumFailure;
use Afk11\Bitcoin\Exceptions\InvalidPrivateKey;
use Afk11\Bitcoin\Math\Math;
use Afk11\Bitcoin\NetworkInterface;
use Afk11\Bitcoin\Crypto\Hash;
use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Base58;
use Afk11\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use Afk11\Bitcoin\Serializer\Key\HierarchicalKey\HexExtendedKeySerializer;
use Mdanter\Ecc\GeneratorPoint;

class HierarchicalKeyFactory
{
    /**
     * @param NetworkInterface $network
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return HierarchicalKey
     */
    public static function generateMasterKey(Math $math = null, GeneratorPoint $generator = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        $buffer  = PrivateKeyFactory::generate();
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
        $chainCode = Buffer::hex(substr($hash, 64, 64));
        $private = PrivateKeyFactory::fromHex(substr($hash, 0, 64));

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

        $extSerializer = new ExtendedKeySerializer($network, new HexExtendedKeySerializer($math, $generator, $network));
        $key = $extSerializer->parse($extendedKey);
        return $key;
    }
}
