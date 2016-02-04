<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Serializer\Signature\DerSignatureSerializer;
use BitWasp\Bitcoin\Serializable;

class Signature extends Serializable implements SignatureInterface
{
    /**
     * @var int|string
     */
    private $r;

    /**
     * @var  int|string
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
     * @param int|string $r
     * @param int|string $s
     * @param resource $secp256k1_ecdsa_signature_t
     */
    public function __construct(EcAdapter $adapter, $r, $s, $secp256k1_ecdsa_signature_t)
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
     * @return int|string
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * @return int|string
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
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function getBuffer()
    {
        return (new DerSignatureSerializer($this->ecAdapter))->serialize($this);
    }
}
