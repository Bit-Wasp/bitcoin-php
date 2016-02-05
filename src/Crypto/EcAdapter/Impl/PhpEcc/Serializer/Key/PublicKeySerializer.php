<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;
use Mdanter\Ecc\Primitives\PointInterface;

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
     * @param bool $compressed
     * @param PointInterface $point
     * @return string
     */
    public function getPrefix($compressed, PointInterface $point)
    {
        return $compressed
            ? $this->ecAdapter->getMath()->isEven($point->getY())
                ? PublicKey::KEY_COMPRESSED_EVEN
                : PublicKey::KEY_COMPRESSED_ODD
            : PublicKey::KEY_UNCOMPRESSED;
    }

    /**
     * @param PublicKey $publicKey
     * @return BufferInterface
     */
    private function doSerialize(PublicKey $publicKey)
    {
        $math = $this->ecAdapter->getMath();
        $point = $publicKey->getPoint();
        $compressed = $publicKey->isCompressed();

        $parser = new Parser('', $math);
        $parser->writeBytes(1, $this->getPrefix($compressed, $point));

        $compressed
            ? $parser
            ->writeBytes(32, Buffer::int($point->getX(), null, $math))
            : $parser
            ->writeBytes(32, Buffer::int($point->getX(), null, $math))
            ->writeBytes(32, Buffer::int($point->getY(), null, $math));

        return $parser->getBuffer();
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return BufferInterface
     */
    public function serialize(PublicKeyInterface $publicKey)
    {
        /** @var PublicKey $publicKey */
        return $this->doSerialize($publicKey);
    }

    /**
     * @param BufferInterface|string $data
     * @return PublicKey
     * @throws \Exception
     */
    public function parse($data)
    {
        $buffer = (new Parser($data))->getBuffer();
        if (!in_array($buffer->getSize(), [PublicKey::LENGTH_COMPRESSED, PublicKey::LENGTH_UNCOMPRESSED], true)) {
            throw new \Exception('Invalid hex string, must match size of compressed or uncompressed public key');
        }

        return $this->ecAdapter->publicKeyFromBuffer($buffer);
    }
}
