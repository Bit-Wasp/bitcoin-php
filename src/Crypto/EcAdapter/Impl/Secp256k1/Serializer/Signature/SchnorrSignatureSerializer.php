<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature\SchnorrSignature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\SchnorrSignatureSerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SchnorrSignatureInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

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
     * @param SchnorrSignature $signature
     * @return BufferInterface
     */
    private function doSerialize(SchnorrSignature $signature): BufferInterface
    {
        $sigOut = '';
        if (!secp256k1_schnorrsig_serialize($this->ecAdapter->getContext(), $sigOut, $signature->getResource())) {
            throw new \RuntimeException('Secp256k1 serialize compact failure');
        }

        return new Buffer($sigOut, 64);
    }

    /**
     * @param SchnorrSignatureInterface $signature
     * @return BufferInterface
     */
    public function serialize(SchnorrSignatureInterface $signature): BufferInterface
    {
        /** @var SchnorrSignature $signature */
        return $this->doSerialize($signature);
    }

    /**
     * @param BufferInterface $sig
     * @return SchnorrSignatureInterface
     * @throws \Exception
     */
    public function parse(BufferInterface $sig): SchnorrSignatureInterface
    {
        if ($sig->getSize() !== 64) {
            throw new \RuntimeException('Compact Sig must be 65 bytes');
        }

        $sig_t = null;
        if (!secp256k1_schnorrsig_parse($this->ecAdapter->getContext(), $sig_t, $sig->getBinary())) {
            throw new \RuntimeException('Unable to parse compact signature');
        }
        /** @var resource $sig_t */
        return new SchnorrSignature($this->ecAdapter, $sig_t);
    }
}
