<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Serializer\Signature\CompactSignatureSerializer;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Buffertools\Buffer;

class CompactSignature extends Serializable implements CompactSignatureInterface
{
    /**
     * @var int|string
     */
    private $r;

    /**
     * @var int|string
     */
    private $s;

    /**
     * @var resource
     */
    private $resource;

    /**
     * @var bool
     */
    private $compressed;

    /**
     * @var int
     */
    private $recid;

    /**
     * @var EcAdapter
     */
    private $ecAdapter;

    /**
     * @param EcAdapter $ecAdapter
     * @param resource $secp256k1_ecdsa_signature_t
     * @param bool $compressed
     */
    public function __construct(EcAdapter $ecAdapter, $secp256k1_ecdsa_signature_t, $recid, $compressed)
    {
        $math = $ecAdapter->getMath();
        if (!is_bool($compressed)) {
            throw new \InvalidArgumentException('CompactSignature: compressed must be a boolean');
        }

        $ser = '';
        $recidout = '';
        secp256k1_ecdsa_recoverable_signature_serialize_compact($ecAdapter->getContext(), $secp256k1_ecdsa_signature_t, $ser, $recidout);
        list ($r, $s) = array_map(
            function ($val) use ($math) {
                return $math->hexDec(bin2hex($val));
            },
            str_split($ser, 32)
        );

        $this->resource = $secp256k1_ecdsa_signature_t;
        $this->r = $r;
        $this->s = $s;
        $this->recid = $recid;
        $this->compressed = $compressed;
        $this->ecAdapter = $ecAdapter;
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
        return $this->resource;
    }

    /**
     * @return int
     */
    public function getRecoveryId()
    {
        return $this->recid;
    }

    /**
     * @return int|string
     */
    public function getFlags()
    {
        return $this->getRecoveryId() + 27 + ($this->isCompressed() ? 4 : 0);
    }

    /**
     * @return bool
     */
    public function isCompressed()
    {
        return $this->compressed;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new CompactSignatureSerializer($this->ecAdapter))->serialize($this);
    }
}
