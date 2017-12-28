<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PrivateKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Exceptions\InvalidPrivateKey;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class PrivateKeyFactory
{
    /**
     * @param bool $compressed
     * @param EcAdapterInterface|null $ecAdapter
     * @return PrivateKeyInterface
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public static function create(bool $compressed = false, EcAdapterInterface $ecAdapter = null): PrivateKeyInterface
    {
        return self::fromBuffer(self::generateSecret(), $compressed, $ecAdapter);
    }

    /**
     * Generate a buffer containing a valid key
     *
     * @param EcAdapterInterface|null $ecAdapter
     * @return BufferInterface
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public static function generateSecret(EcAdapterInterface $ecAdapter = null): BufferInterface
    {
        $random = new Random();
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();

        do {
            $buffer = $random->bytes(32);
        } while (!$ecAdapter->validatePrivateKey($buffer));

        return $buffer;
    }

    /**
     * @param string $hex
     * @param bool $compressed
     * @param EcAdapterInterface|null $ecAdapter
     * @return PrivateKeyInterface
     * @throws \Exception
     */
    public static function fromHex(string $hex, bool $compressed = false, EcAdapterInterface $ecAdapter = null): PrivateKeyInterface
    {
        return self::fromBuffer(Buffer::hex($hex), $compressed, $ecAdapter);
    }

    /**
     * @param BufferInterface $buffer
     * @param bool $compressed
     * @param EcAdapterInterface $ecAdapter
     * @return PrivateKeyInterface
     */
    public static function fromBuffer(BufferInterface $buffer, bool $compressed, EcAdapterInterface $ecAdapter = null): PrivateKeyInterface
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();

        /** @var PrivateKeySerializerInterface $serializer */
        $serializer = EcSerializer::getSerializer(PrivateKeySerializerInterface::class, true, $ecAdapter);

        $parsed = $serializer->parse($buffer);
        if ($compressed) {
            $parsed = $ecAdapter->getPrivateKey($parsed->getSecret(), $compressed);
        }

        return $parsed;
    }

    /**
     * @param int|string $int
     * @param bool $compressed
     * @param EcAdapterInterface|null $ecAdapter
     * @return PrivateKeyInterface
     */
    public static function fromInt($int, bool $compressed = false, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $secret = Buffer::int($int, 32)->getGmp();
        return $ecAdapter->getPrivateKey($secret, $compressed);
    }

    /**
     * @param string $wif
     * @param EcAdapterInterface|null $ecAdapter
     * @param NetworkInterface|null $network
     * @return PrivateKeyInterface
     * @throws InvalidPrivateKey
     * @throws \BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
     */
    public static function fromWif(string $wif, EcAdapterInterface $ecAdapter = null, NetworkInterface $network = null)
    {
        if (null === $ecAdapter) {
            $ecAdapter = Bitcoin::getEcAdapter();
        }

        $serializer = EcSerializer::getSerializer(PrivateKeySerializerInterface::class, true, $ecAdapter);
        $wifSerializer = new WifPrivateKeySerializer($ecAdapter, $serializer);

        return $wifSerializer->parse($wif, $network);
    }
}
