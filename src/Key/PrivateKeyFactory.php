<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Exceptions\InvalidPrivateKey;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory as PrivKeyFactory;
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
        return (new PrivKeyFactory($compressed, $ecAdapter ?: Bitcoin::getEcAdapter()))
            ->generate(new Random())
        ;
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
        return (new PrivKeyFactory($compressed, $ecAdapter ?: Bitcoin::getEcAdapter()))
            ->fromHex($hex)
        ;
    }

    /**
     * @param BufferInterface $buffer
     * @param bool $compressed
     * @param EcAdapterInterface $ecAdapter
     * @return PrivateKeyInterface
     */
    public static function fromBuffer(BufferInterface $buffer, bool $compressed, EcAdapterInterface $ecAdapter = null): PrivateKeyInterface
    {
        return (new PrivKeyFactory($compressed, $ecAdapter ?: Bitcoin::getEcAdapter()))
            ->fromBuffer($buffer)
        ;
    }

    /**
     * @param int|string $int
     * @param bool $compressed
     * @param EcAdapterInterface|null $ecAdapter
     * @return PrivateKeyInterface
     */
    public static function fromInt($int, bool $compressed = false, EcAdapterInterface $ecAdapter = null)
    {
        return (new PrivKeyFactory($compressed, $ecAdapter ?: Bitcoin::getEcAdapter()))
            ->fromBuffer(Buffer::int($int, 32))
        ;
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
        return (new PrivKeyFactory(true, $ecAdapter ?: Bitcoin::getEcAdapter()))
            ->fromWif($wif, $network)
        ;
    }
}
