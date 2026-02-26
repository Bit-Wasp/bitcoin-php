<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature\SchnorrSignature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\SchnorrSignatureSerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SchnorrSignatureInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;

class SchnorrSignatureSerializer implements SchnorrSignatureSerializerInterface
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
     * @param SchnorrSignature $sig
     * @return BufferInterface
     */
    private function doSerialize(SchnorrSignature $sig): BufferInterface
    {
        return Buffertools::concat(
            Buffer::int(gmp_strval($sig->getR()), 32),
            Buffer::int(gmp_strval($sig->getS()), 32)
        );
    }

    /**
     * @param SchnorrSignatureInterface $sig
     * @return BufferInterface
     */
    public function serialize(SchnorrSignatureInterface $sig): BufferInterface
    {
        /** @var SchnorrSignature $sig */
        return $this->doSerialize($sig);
    }

    /**
     * @param BufferInterface $buffer
     * @return SchnorrSignatureInterface
     * @throws \Exception
     */
    public function parse(BufferInterface $buffer): SchnorrSignatureInterface
    {
        if ($buffer->getSize() !== 64) {
            throw new \RuntimeException("schnorrsig must be 64 bytes");
        }
        $r = $buffer->slice(0, 32)->getGmp();
        $s = $buffer->slice(32, 32)->getGmp();
        return new SchnorrSignature($r, $s);
    }
}
