<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Buffertools\BufferInterface;

class PublicKeyFactory
{
    /**
     * @param EcAdapterInterface $ecAdapter
     * @return PublicKeySerializerInterface
     */
    public static function getSerializer(EcAdapterInterface $ecAdapter = null)
    {
        return EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
    }

    /**
     * @param BufferInterface|string $hex
     * @param EcAdapterInterface|null $ecAdapter
     * @return PublicKeyInterface
     */
    public static function fromHex($hex, EcAdapterInterface $ecAdapter = null): PublicKeyInterface
    {
        return self::getSerializer($ecAdapter)->parse($hex);
    }

    /**
     * @param BufferInterface|string $hex
     * @param EcAdapterInterface|null $ecAdapter
     * @return bool
     */
    public static function validateHex($hex, EcAdapterInterface $ecAdapter = null)
    {
        try {
            self::fromHex($hex, $ecAdapter);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
