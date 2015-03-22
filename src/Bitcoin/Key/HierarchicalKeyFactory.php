<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\HexExtendedKeySerializer;
use Mdanter\Ecc\GeneratorPoint;

class HierarchicalKeyFactory
{
    /**
     * @param Math $math
     * @param $generator
     * @param NetworkInterface $network
     * @return ExtendedKeySerializer
     */
    public static function getSerializer($math, $generator, $network = null)
    {
        $extSerializer = new ExtendedKeySerializer(new HexExtendedKeySerializer($math, $generator, $network));
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
        $private = self::fromEntropy($buffer->getBuffer()->serialize('hex'));
        return $private;
    }

    /**
     * @param string $entropy
     * @param Math $math
     * @param GeneratorPoint $generator
     * @return HierarchicalKey
     */
    public static function fromEntropy($entropy, Math $math = null, GeneratorPoint $generator = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        $hash = Hash::hmac('sha512', pack("H*", $entropy), "Bitcoin seed");

        $key = new HierarchicalKey(
            $math,
            $generator,
            0,
            0,
            0,
            $math->hexDec(substr($hash, 64, 64)),
            PrivateKeyFactory::fromHex(substr($hash, 0, 64), true)
        );

        return $key;
    }

    /**
     * @param string $extendedKey
     * @param NetworkInterface $network
     * @return HierarchicalKey
     * @throws Base58ChecksumFailure
     */
    public static function fromExtended($extendedKey, NetworkInterface $network = null, Math $math = null, GeneratorPoint $generator = null)
    {
        $network = $math ?: Bitcoin::getNetwork();
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        $extSerializer = self::getSerializer($math, $generator, $network);
        $key = $extSerializer->parse($extendedKey);
        return $key;
    }
}
