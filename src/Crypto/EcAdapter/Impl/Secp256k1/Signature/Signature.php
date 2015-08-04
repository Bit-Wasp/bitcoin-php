<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Buffertools\Buffer;

class Signature extends Serializable implements SignatureInterface
{
    /**
     * @var int
     */
    private $r;

    /**
     * @var int
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
     * @param resource $secp256k1_ecdsa_signature_t
     */
    public function __construct(EcAdapter $adapter, $secp256k1_ecdsa_signature_t)
    {
        $sig = '';
        if (1 !== secp256k1_ecdsa_signature_serialize_compact($adapter->getContext(), $secp256k1_ecdsa_signature_t, $sig)) {
            throw new \RuntimeException('Secp256k1: Failed to parse signature');
        }

        $math = $adapter->getMath();
        list ($r, $s) = array_map(
            function ($binaryValue) use ($math) {
                return $math->hexDec(bin2hex($binaryValue));
            },
            str_split($sig, 32)
        );

        $this->r = $r;
        $this->s = $s;
        $this->secp256k1_sig = $secp256k1_ecdsa_signature_t;
        $this->ecAdapter = $adapter;
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->secp256k1_sig;
    }
    /**
     * @inheritdoc
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * @inheritdoc
     */
    public function getS()
    {
        return $this->s;
    }

    /**
     * @return \BitWasp\Buffertools\Buffer
     */
    public function getBuffer()
    {
        $sigOut = '';
        if (!secp256k1_ecdsa_signature_serialize_der($this->ecAdapter->getContext(), $this->secp256k1_sig, $sigOut)) {
            throw new \RuntimeException('Secp256k1: failed to serialize DER signature');
        }

        return new Buffer($sigOut);
    }
}
