<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Serializer\Signature\DerSignatureSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Buffertools\BufferInterface;

class Signature extends Serializable implements SignatureInterface
{
    /**
     * @var \GMP
     */
    private $r;

    /**
     * @var  \GMP
     */
    private $s;

    /**
     * @var EcAdapter
     */
    private $ecAdapter;

    /**
     * @var resource
     */
    private $secp256k1_sig;

    /**
     * @param EcAdapter $adapter
     * @param \GMP $r
     * @param \GMP $s
     * @param resource $secp256k1_ecdsa_signature_t
     */
    public function __construct(EcAdapter $adapter, \GMP $r, \GMP $s, $secp256k1_ecdsa_signature_t)
    {
        if (!is_resource($secp256k1_ecdsa_signature_t) ||
            !get_resource_type($secp256k1_ecdsa_signature_t) === SECP256K1_TYPE_SIG
        ) {
            throw new \InvalidArgumentException('Secp256k1\Signature\Signature expects ' . SECP256K1_TYPE_SIG . ' resource');
        }

        $this->secp256k1_sig = $secp256k1_ecdsa_signature_t;
        $this->ecAdapter = $adapter;
        $this->r = $r;
        $this->s = $s;
    }

    /**
     * @return \GMP
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * @return \GMP
     */
    public function getS()
    {
        return $this->s;
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->secp256k1_sig;
    }

    /**
     * @param Signature $other
     * @return bool
     */
    private function doEquals(Signature $other): bool
    {
        $a = '';
        $b = '';
        secp256k1_ecdsa_signature_serialize_der($this->ecAdapter->getContext(), $a, $this->getResource());
        secp256k1_ecdsa_signature_serialize_der($this->ecAdapter->getContext(), $b, $other->getResource());

        return hash_equals($a, $b);
    }

    /**
     * @param SignatureInterface $signature
     * @return bool
     */
    public function equals(SignatureInterface $signature): bool
    {
        /** @var Signature $signature */
        return $this->doEquals($signature);
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer(): BufferInterface
    {
        return (new DerSignatureSerializer($this->ecAdapter))->serialize($this);
    }
}
