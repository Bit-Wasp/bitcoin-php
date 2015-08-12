<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Serializer\Key\PublicKey\HexPublicKeySerializer;
use Mdanter\Ecc\Primitives\PointInterface;

class PublicKeyFactory
{
    /**
     * @param EcAdapterInterface $ecAdapter
     * @return PublicKeySerializerInterface
     */
    public static function getSerializer(EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $hexSerializer = EcSerializer::getSerializer($ecAdapter, PublicKeySerializerInterface::class);
        return $hexSerializer;
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

    public static function validateHex($hex, EcAdapterInterface $ecAdapter = null)
    {
        try {
            self::fromHex($hex, $ecAdapter);
            $valid = true;
        } catch (\Exception $e) {
            $valid = false;
        }

        return $valid;
    }
}
