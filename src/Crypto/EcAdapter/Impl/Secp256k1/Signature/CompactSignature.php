<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Serializer\Signature\CompactSignatureSerializer;
use BitWasp\Buffertools\Buffer;

class CompactSignature extends Signature implements CompactSignatureInterface
{
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
        secp256k1_ecdsa_signature_serialize_compact($ecAdapter->getContext(), $secp256k1_ecdsa_signature_t, $ser, $recidout);
        list ($r, $s) = array_map(
            function ($val) use ($math) {
                return $math->hexDec(bin2hex($val));
            },
            str_split($ser, 32)
        );

        parent::__construct($ecAdapter, $r, $s, $secp256k1_ecdsa_signature_t);
        $this->recid = $recid;
        $this->compressed = $compressed;
        $this->ecAdapter = $ecAdapter;
    }

    /**
     * @return int
     */
    public function getRecoveryId()
    {
        if (null == $this->recid) {
            $context = $this->ecAdapter->getContext();
            $recid = '';
            $sig_ser = '';
            if (1 !== secp256k1_ecdsa_signature_serialize_compact($context, $this->getResource(), $sig_ser, $recid)) {
                throw new \RuntimeException('Error serializing der sig');
            }
            $this->recid = $recid;
        }

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
