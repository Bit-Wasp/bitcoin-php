<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class PublicKeyFactory
{
    /**
     * @param string $hex
     * @param EcAdapterInterface|null $ecAdapter
     * @return PublicKeyInterface
     * @throws \Exception
     */
    public static function fromHex(string $hex, EcAdapterInterface $ecAdapter = null): PublicKeyInterface
    {
        return self::fromBuffer(Buffer::hex($hex), $ecAdapter);
    }

    /**
     * @param BufferInterface $buffer
     * @param EcAdapterInterface|null $ecAdapter
     * @return PublicKeyInterface
     */
    public static function fromBuffer(BufferInterface $buffer, EcAdapterInterface $ecAdapter = null): PublicKeyInterface
    {
        return EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter)
            ->parse($buffer)
        ;
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
