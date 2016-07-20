<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PrivateKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Exceptions\InvalidPrivateKey;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;

class PrivateKeyFactory
{
    /**
     * Generate a buffer containing a valid key
     *
     * @param EcAdapterInterface|null $ecAdapter
     * @return \BitWasp\Buffertools\BufferInterface
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public static function generateSecret(EcAdapterInterface $ecAdapter = null)
    {
        $random = new Random();
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();

        do {
            $buffer = $random->bytes(32);
        } while (!$ecAdapter->validatePrivateKey($buffer));

        return $buffer;
    }

    /**
     * @param int|string $int
     * @param bool $compressed
     * @param EcAdapterInterface|null $ecAdapter
     * @return PrivateKeyInterface
     */
    public static function fromInt($int, $compressed = false, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        return $ecAdapter->getPrivateKey(gmp_init($int, 10), $compressed);
    }

    /**
     * @param bool $compressed
     * @param EcAdapterInterface|null $ecAdapter
     * @return PrivateKeyInterface
     */
    public static function create($compressed = false, EcAdapterInterface $ecAdapter = null)
    {
        $secret = self::generateSecret();
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        return self::fromInt($secret->getInt(), $compressed, $ecAdapter);
    }

    /**
     * @param string $wif
     * @param EcAdapterInterface|null $ecAdapter
     * @param NetworkInterface $network
     * @return \BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface
     * @throws InvalidPrivateKey
     */
    public static function fromWif($wif, EcAdapterInterface $ecAdapter = null, NetworkInterface $network = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $network = $network ?: Bitcoin::getNetwork();
        $serializer = EcSerializer::getSerializer(PrivateKeySerializerInterface::class);
        $wifSerializer = new WifPrivateKeySerializer($ecAdapter->getMath(), $serializer);

        return $wifSerializer->parse($wif, $network);
    }

    /**
     * @param \BitWasp\Buffertools\BufferInterface|string $hex
     * @param bool $compressed
     * @param EcAdapterInterface|null $ecAdapter
     * @return PrivateKeyInterface
     */
    public static function fromHex($hex, $compressed = false, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();

        /** @var PrivateKeySerializerInterface $serializer */
        $serializer = EcSerializer::getSerializer(PrivateKeySerializerInterface::class);

        $parsed = $serializer->parse($hex);
        if ($compressed) {
            $parsed = $ecAdapter->getPrivateKey($parsed->getSecret(), $compressed);
        }

        return $parsed;
    }
}
