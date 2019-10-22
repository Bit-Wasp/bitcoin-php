<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Serializer\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key\PublicKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key\XOnlyPublicKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\XOnlyPublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\XOnlyPublicKeySerializerInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class XOnlyPublicKeySerializer implements XOnlyPublicKeySerializerInterface
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
    private function doSerialize(XOnlyPublicKey $publicKey)
    {
        $serialized = '';
        if (!secp256k1_xonly_pubkey_serialize(
            $this->ecAdapter->getContext(),
            $serialized,
            $publicKey->getResource()
        )) {
            throw new \RuntimeException('Secp256k1: Failed to serialize xonly public key');
        }

        return new Buffer($serialized, 32);
    }

    /**
     * @param XOnlyPublicKeyInterface $publicKey
     * @return BufferInterface
     */
    public function serialize(XOnlyPublicKeyInterface $publicKey): BufferInterface
    {
        /** @var PublicKey $publicKey */
        return $this->doSerialize($publicKey);
    }

    /**
     * @param BufferInterface $buffer
     * @return XOnlyPublicKeyInterface
     */
    public function parse(BufferInterface $buffer): XOnlyPublicKeyInterface
    {
        $binary = $buffer->getBinary();
        $xonlyPubkey = null;
        if (!secp256k1_xonly_pubkey_parse($this->ecAdapter->getContext(), $xonlyPubkey, $binary)) {
            throw new \RuntimeException('Secp256k1 failed to parse xonly public key');
        }
        /** @var resource $xonlyPubkey */
        return new XOnlyPublicKey(
            $this->ecAdapter->getContext(),
            $xonlyPubkey
        );
    }
}
