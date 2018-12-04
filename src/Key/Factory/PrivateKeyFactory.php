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
     * @var PrivateKeySerializerInterface
     */
    private $privSerializer;

    /**
     * @var WifPrivateKeySerializer
     */
    private $wifSerializer;

    /**
     * PrivateKeyFactory constructor.
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $this->privSerializer = EcSerializer::getSerializer(PrivateKeySerializerInterface::class, true, $ecAdapter);
        $this->wifSerializer = new WifPrivateKeySerializer($this->privSerializer);
    }
    
    /**
     * @param Random $random
     * @return PrivateKeyInterface
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function generateCompressed(Random $random): PrivateKeyInterface
    {
        return $this->privSerializer->parse($random->bytes(32), true);
    }

    /**
     * @param Random $random
     * @return PrivateKeyInterface
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function generateUncompressed(Random $random): PrivateKeyInterface
    {
        return $this->privSerializer->parse($random->bytes(32), false);
    }

    /**
     * @param BufferInterface $raw
     * @return PrivateKeyInterface
     * @throws \Exception
     */
    public function fromBufferCompressed(BufferInterface $raw): PrivateKeyInterface
    {
        return $this->privSerializer->parse($raw, true);
    }

    /**
     * @param BufferInterface $raw
     * @return PrivateKeyInterface
     * @throws \Exception
     */
    public function fromBufferUncompressed(BufferInterface $raw): PrivateKeyInterface
    {
        return $this->privSerializer->parse($raw, false);
    }

    /**
     * @param string $hex
     * @return PrivateKeyInterface
     * @throws \Exception
     */
    public function fromHexCompressed(string $hex): PrivateKeyInterface
    {
        return $this->fromBufferCompressed(Buffer::hex($hex));
    }

    /**
     * @param string $hex
     * @return PrivateKeyInterface
     * @throws \Exception
     */
    public function fromHexUncompressed(string $hex): PrivateKeyInterface
    {
        return $this->fromBufferUncompressed(Buffer::hex($hex));
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
