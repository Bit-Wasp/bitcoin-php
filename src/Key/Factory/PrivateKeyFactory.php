<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\Factory;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PrivateKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class PrivateKeyFactory
{
    /**
     * @var bool
     */
    private $compressed;

    /**
     * @var PrivateKeySerializerInterface
     */
    private $privSerializer;

    /**
     * @var WifPrivateKeySerializer
     */
    private $wifSerializer;

    /**
     * PrivateKeyFactory constructor.
     * @param bool $compressed
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(bool $compressed, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $this->privSerializer = EcSerializer::getSerializer(PrivateKeySerializerInterface::class, true, $ecAdapter);
        $this->wifSerializer = new WifPrivateKeySerializer($this->privSerializer);
        $this->compressed = $compressed;
    }

    /**
     * @param EcAdapterInterface|null $ecAdapter
     * @return PrivateKeyFactory
     */
    public static function uncompressed(EcAdapterInterface $ecAdapter = null): PrivateKeyFactory
    {
        return new self(false, $ecAdapter);
    }

    /**
     * @param EcAdapterInterface|null $ecAdapter
     * @return PrivateKeyFactory
     */
    public static function compressed(EcAdapterInterface $ecAdapter = null): PrivateKeyFactory
    {
        return new self(true, $ecAdapter);
    }

    /**
     * @return bool
     */
    public function isCompressed(): bool
    {
        return $this->compressed;
    }

    /**
     * @param Random $random
     * @return PrivateKeyInterface
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function generate(Random $random): PrivateKeyInterface
    {
        return $this->fromBuffer(
            $random->bytes(32)
        );
    }

    /**
     * @param string $hex
     * @return PrivateKeyInterface
     * @throws \Exception
     */
    public function fromHex(string $hex): PrivateKeyInterface
    {
        return $this->fromBuffer(Buffer::hex($hex));
    }

    /**
     * @param BufferInterface $buffer
     * @return PrivateKeyInterface
     */
    public function fromBuffer(BufferInterface $buffer): PrivateKeyInterface
    {
        return $this->privSerializer->parse(
            $buffer,
            $this->compressed
        );
    }

    /**
     * @param string $wif
     * @param NetworkInterface $network
     * @return PrivateKeyInterface
     * @throws \BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidPrivateKey
     * @throws \Exception
     */
    public function fromWif(string $wif, NetworkInterface $network = null): PrivateKeyInterface
    {
        return $this->wifSerializer->parse($wif, $network);
    }
}
