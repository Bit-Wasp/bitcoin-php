<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\XOnlyPublicKey;
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

    private function doSerialize(XOnlyPublicKey $publicKey): BufferInterface
    {
        $x = $publicKey->getPoint()->getX();
        return Buffer::int(gmp_strval($x), 32);
    }

    /**
     * @param XOnlyPublicKeyInterface $publicKey
     * @return BufferInterface
     */
    public function serialize(XOnlyPublicKeyInterface $publicKey): BufferInterface
    {
        return $this->doSerialize($publicKey);
    }

    /**
     * @param BufferInterface $buffer
     * @return XOnlyPublicKeyInterface
     */
    public function parse(BufferInterface $buffer): XOnlyPublicKeyInterface
    {
        if ($buffer->getSize() !== 32) {
            throw new \RuntimeException("incorrect size");
        }
        $x = $buffer->getGmp();
    }
}
