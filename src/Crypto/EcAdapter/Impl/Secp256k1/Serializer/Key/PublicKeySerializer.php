<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Serializer\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key\PublicKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;

class PublicKeySerializer implements PublicKeySerializerInterface
{
    /**
     * @var EcAdapter
     */
    private $ecAdapter;

    /**
     * @param EcAdapter $ecAdapter
     */
    public function __construct(EcAdapter $ecAdapter)
    {
        $this->ecAdapter = $ecAdapter;
    }

    /**
     * @param PublicKey $publicKey
     * @return BufferInterface
     */
    private function doSerialize(PublicKey $publicKey)
    {
        $serialized = '';
        $isCompressed = $publicKey->isCompressed();
        if (!secp256k1_ec_pubkey_serialize(
            $this->ecAdapter->getContext(),
            $serialized,
            $publicKey->getResource(),
            $isCompressed ? SECP256K1_EC_COMPRESSED : SECP256K1_EC_UNCOMPRESSED
        )) {
            throw new \RuntimeException('Secp256k1: Failed to serialize public key');
        }

        return new Buffer(
            $serialized,
            $isCompressed ? PublicKey::LENGTH_COMPRESSED : PublicKey::LENGTH_UNCOMPRESSED
        );
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return BufferInterface
     */
    public function serialize(PublicKeyInterface $publicKey): BufferInterface
    {
        /** @var PublicKey $publicKey */
        return $this->doSerialize($publicKey);
    }

    /**
     * @param BufferInterface $buffer
     * @return PublicKeyInterface
     */
    public function parse(BufferInterface $buffer): PublicKeyInterface
    {
        $binary = $buffer->getBinary();
        $pubkey_t = null;
        if (!secp256k1_ec_pubkey_parse($this->ecAdapter->getContext(), $pubkey_t, $binary)) {
            throw new \RuntimeException('Secp256k1 failed to parse public key');
        }
        /** @var resource $pubkey_t */
        return new PublicKey(
            $this->ecAdapter,
            $pubkey_t,
            $buffer->getSize() === 33
        );
    }
}
