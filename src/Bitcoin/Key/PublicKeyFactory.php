<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Serializer\Key\PublicKey\HexPublicKeySerializer;
use Mdanter\Ecc\Primitives\PointInterface;

class PublicKeyFactory
{
    /**
     * @param EcAdapterInterface $ecAdapter
     * @return HexPublicKeySerializer
     */
    public static function getSerializer(EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();

        $hexSerializer = new HexPublicKeySerializer($ecAdapter);
        return $hexSerializer;
    }

    /**
     * @param PointInterface $point
     * @param bool $compressed
     * @param EcAdapterInterface $ecAdapter
     * @return PublicKey
     */
    public static function fromPoint(PointInterface $point, $compressed = false, EcAdapterInterface $ecAdapter = null)
    {
        return new PublicKey(
            $ecAdapter ?: Bitcoin::getEcAdapter(),
            $point,
            $compressed
        );
    }

    /**
     * @param string $hex
     * @param EcAdapterInterface $ecAdapter
     * @return PublicKey
     * @throws \Exception
     */
    public static function fromHex($hex, EcAdapterInterface $ecAdapter = null)
    {
        return self::getSerializer($ecAdapter)->parse($hex);
    }
}
