<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\XOnlyPublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\XOnlyPublicKeySerializerInterface;
use BitWasp\Buffertools\BufferInterface;

class SchnorrSignatureSerializer implements XOnlyPublicKeySerializerInterface
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
     * @param XOnlyPublicKeyInterface $publicKey
     * @return BufferInterface
     */
    public function serialize(XOnlyPublicKeyInterface $publicKey): BufferInterface
    {
        throw new \RuntimeException("not implemented");
    }

    /**
     * @param BufferInterface $buffer
     * @return XOnlyPublicKeyInterface
     */
    public function parse(BufferInterface $buffer): XOnlyPublicKeyInterface
    {
        throw new \RuntimeException("not implemented");
    }
}
