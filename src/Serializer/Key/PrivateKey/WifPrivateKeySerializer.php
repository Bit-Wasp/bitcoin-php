<?php

namespace BitWasp\Bitcoin\Serializer\Key\PrivateKey;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PrivateKeySerializerInterface;
use BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure;
use BitWasp\Bitcoin\Exceptions\InvalidPrivateKey;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;

class WifPrivateKeySerializer
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var PrivateKeySerializerInterface
     */
    private $keySerializer;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param PrivateKeySerializerInterface $serializer
     */
    public function __construct(EcAdapterInterface $ecAdapter, PrivateKeySerializerInterface $serializer)
    {
        $this->ecAdapter = $ecAdapter;
        $this->keySerializer = $serializer;
    }

    /**
     * @param NetworkInterface $network
     * @param PrivateKeyInterface $privateKey
     * @return string
     */
    public function serialize(NetworkInterface $network, PrivateKeyInterface $privateKey)
    {
        $math = $this->ecAdapter->getMath();
        $serialized = Buffertools::concat(
            Buffer::hex($network->getPrivByte(), 1, $math),
            $this->keySerializer->serialize($privateKey)
        );

        if ($privateKey->isCompressed()) {
            $serialized = Buffertools::concat(
                $serialized,
                new Buffer("\x01", 1, $math)
            );
        }

        return Base58::encodeCheck($serialized);
    }

    /**
     * @param string $wif
     * @param NetworkInterface|null $network
     * @return PrivateKeyInterface
     * @throws Base58ChecksumFailure
     * @throws InvalidPrivateKey
     */
    public function parse($wif, NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        $data = Base58::decodeCheck($wif);
        if ($data->slice(0, 1)->getHex() !== $network->getPrivByte()) {
            throw new \RuntimeException('WIF prefix does not match networks');
        }

        $payload = $data->slice(1);
        $size = $payload->getSize();

        if (33 === $size) {
            $compressed = true;
            $payload = $payload->slice(0, 32);
        } else if (32 === $size) {
            $compressed = false;
        } else {
            throw new InvalidPrivateKey("Private key should be always be 32 or 33 bytes (depending on if it's compressed)");
        }

        return PrivateKeyFactory::fromInt($payload->getInt(), $compressed, $this->ecAdapter);
    }
}
