<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
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
     * @return string
     */
    public function getPrefix(PublicKey $publicKey)
    {
        if (null === $publicKey->getPrefix()) {
            return $publicKey->isCompressed()
                ? $this->ecAdapter->getMath()->isEven($publicKey->getPoint()->getY())
                    ? PublicKey::KEY_COMPRESSED_EVEN
                    : PublicKey::KEY_COMPRESSED_ODD
                : PublicKey::KEY_UNCOMPRESSED;
        } else {
            return $publicKey->getPrefix();
        }
    }

    /**
     * @param PublicKey $publicKey
     * @return BufferInterface
     */
    private function doSerialize(PublicKey $publicKey)
    {
        $math = $this->ecAdapter->getMath();
        $point = $publicKey->getPoint();

        $length = 33;
        $data = $this->getPrefix($publicKey) . Buffer::int(gmp_strval($point->getX(), 10), 32, $math)->getBinary();
        if (!$publicKey->isCompressed()) {
            $length = 65;
            $data .= Buffer::int(gmp_strval($point->getY(), 10), 32, $math)->getBinary();
        }

        return new Buffer($data, $length, $math);
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

        /** @var PublicKey $key */
        $key = $this->ecAdapter->publicKeyFromBuffer($buffer);
        return $key;
    }
}
