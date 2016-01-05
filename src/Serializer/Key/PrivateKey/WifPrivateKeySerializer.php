<?php

namespace BitWasp\Bitcoin\Serializer\Key\PrivateKey;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PrivateKeySerializerInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Exceptions\InvalidPrivateKey;
use BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Buffertools\Buffertools;

class WifPrivateKeySerializer
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var PrivateKeySerializerInterface
     */
    private $hexSerializer;

    /**
     * @param Math $math
     * @param PrivateKeySerializerInterface $hexSerializer
     */
    public function __construct(Math $math, PrivateKeySerializerInterface $hexSerializer)
    {
        $this->math = $math;
        $this->hexSerializer = $hexSerializer;
    }

    /**
     * @param NetworkInterface $network
     * @param PrivateKeyInterface $privateKey
     * @return string
     */
    public function serialize(NetworkInterface $network, PrivateKeyInterface $privateKey)
    {
        $serialized = Buffertools::concat(
            Buffer::hex($network->getPrivByte()),
            $this->hexSerializer->serialize($privateKey)
        );

        if ($privateKey->isCompressed()) {
            $serialized = Buffertools::concat(
                $serialized,
                new Buffer("\x01", 1)
            );
        }

        return Base58::encodeCheck($serialized);
    }

    /**
     * @param string $wif
     * @return PrivateKeyInterface
     * @throws InvalidPrivateKey
     * @throws Base58ChecksumFailure
     */
    public function parse($wif)
    {
        $payload = Base58::decodeCheck($wif)->slice(1);
        $size = $payload->getSize();

        if (33 === $size) {
            $compressed = true;
            $payload = $payload->slice(0, 32);
        } else if (32 === $size) {
            $compressed = false;
        } else {
            throw new InvalidPrivateKey("Private key should be always be 32 or 33 bytes (depending on if it's compressed)");
        }

        return PrivateKeyFactory::fromInt($payload->getInt(), $compressed);
    }
}
