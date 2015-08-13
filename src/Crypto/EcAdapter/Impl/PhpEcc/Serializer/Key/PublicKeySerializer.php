<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
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
     * @param $compressed
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
     * @return Buffer
     */
    private function doSerialize(PublicKey $publicKey)
    {
        $point = $publicKey->getPoint();

        $parser = new Parser();
        $parser->writeBytes(1, $this->getPrefix($publicKey->isCompressed(), $point));
        $math = $this->ecAdapter->getMath();

        $publicKey->isCompressed()
            ? $parser
            ->writeBytes(32, $math->decHex($point->getX()))
            : $parser
            ->writeBytes(32, $math->decHex($point->getX()))
            ->writeBytes(32, $math->decHex($point->getY()));

        return $parser->getBuffer();
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return Buffer
     */
    public function serialize(PublicKeyInterface $publicKey)
    {
        /** @var PublicKey $publicKey */
        return $this->doSerialize($publicKey);
    }

    /**
     * @param $hex
     * @return PublicKey
     * @throws \Exception
     */
    public function parse($hex)
    {
        $hex = Buffer::hex($hex);
        if (!in_array($hex->getSize(), [PublicKey::LENGTH_COMPRESSED, PublicKey::LENGTH_UNCOMPRESSED])) {
            throw new \Exception('Invalid hex string, must match size of compressed or uncompressed public key');
        }

        return $this->ecAdapter->publicKeyFromBuffer($hex);
    }
}
