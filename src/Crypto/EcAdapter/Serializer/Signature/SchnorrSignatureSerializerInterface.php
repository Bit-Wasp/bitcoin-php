<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SchnorrSignatureInterface;
use BitWasp\Buffertools\BufferInterface;

interface SchnorrSignatureSerializerInterface
{
    /**
     * @param SchnorrSignatureInterface $signature
     * @return BufferInterface
     */
    public function serialize(SchnorrSignatureInterface $signature): BufferInterface;

    /**
     * @param BufferInterface $derSignature
     * @return SchnorrSignatureInterface
     */
    public function parse(BufferInterface $derSignature): SchnorrSignatureInterface;
}