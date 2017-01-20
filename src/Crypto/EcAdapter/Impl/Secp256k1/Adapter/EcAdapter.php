<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key\PrivateKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key\PublicKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature\CompactSignature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature\Signature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\CompactSignatureInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use Mdanter\Ecc\Primitives\GeneratorPoint;

class EcAdapter implements EcAdapterInterface
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var GeneratorPoint
     */
    private $generator;

    /**
     * @var resource
     */
    private $context;

    /**
     * @param Math $math
     * @param GeneratorPoint $generator
     * @param resource $secp256k1_context_t
     */
    public function __construct(Math $math, GeneratorPoint $generator, $secp256k1_context_t)
    {
        if (!is_resource($secp256k1_context_t) || !get_resource_type($secp256k1_context_t) === SECP256K1_TYPE_CONTEXT) {
            throw new \InvalidArgumentException('Secp256k1: Must pass a secp256k1_context_t resource');
        }
        $this->math = $math;
        $this->generator = $generator;
        $this->context = $secp256k1_context_t;
    }

    /**
     * @return Math
     */
    public function getMath()
    {
        return $this->math;
    }

    /**
     * @return GeneratorPoint
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * @param BufferInterface $privateKey
     * @return bool
     */
    public function validatePrivateKey(BufferInterface $privateKey)
    {
        return (bool) secp256k1_ec_seckey_verify($this->context, $privateKey->getBinary());
    }

    /**
     * @param \GMP $element
     * @param bool $half
     * @return bool
     */
    public function validateSignatureElement(\GMP $element, $half = false)
    {
        $math = $this->getMath();
        $against = $this->getGenerator()->getOrder();
        if ($half) {
            $against = $math->rightShift($against, 1);
        }

        return $math->cmp($element, $against) < 0 && $math->cmp($element, gmp_init(0)) !== 0;
    }

    /**
     * @param \GMP $int
     * @param bool|false $compressed
     * @return PrivateKey
     */
    public function getPrivateKey(\GMP $int, $compressed = false)
    {
        return new PrivateKey($this, $int, $compressed);
    }

    /**
     * @return resource
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param BufferInterface $msg32
     * @param PrivateKey $privateKey
     * @return Signature
     */
    private function doSign(BufferInterface $msg32, PrivateKey $privateKey)
    {
        /** @var resource $sig_t */
        $sig_t = '';
        if (1 !== secp256k1_ecdsa_sign($this->context, $sig_t, $msg32->getBinary(), $privateKey->getBinary())) {
            throw new \RuntimeException('Secp256k1: failed to sign');
        }

        $derSig = '';
        secp256k1_ecdsa_signature_serialize_der($this->context, $derSig, $sig_t);

        $rL = ord($derSig[3]);
        $r = (new Buffer(substr($derSig, 4, $rL), $rL, $this->math))->getGmp();

        $sL = ord($derSig[4+$rL + 1]);
        $s = (new Buffer(substr($derSig, 4 + $rL + 2, $sL), $sL, $this->math))->getGmp();

        return new Signature($this, $r, $s, $sig_t);
    }

    /**
     * @param BufferInterface $msg32
     * @param PrivateKeyInterface $privateKey
     * @param RbgInterface|null $rbg
     * @return Signature
     */
    public function sign(BufferInterface $msg32, PrivateKeyInterface $privateKey, RbgInterface $rbg = null)
    {
        /** @var PrivateKey $privateKey */
        return $this->doSign($msg32, $privateKey);
    }

    /**
     * @param BufferInterface $msg32
     * @param PublicKey $publicKey
     * @param Signature $signature
     * @return bool
     */
    private function doVerify(BufferInterface $msg32, PublicKey $publicKey, Signature $signature)
    {
        return (bool) secp256k1_ecdsa_verify($this->context, $signature->getResource(), $msg32->getBinary(), $publicKey->getResource());
    }

    /**
     * @param BufferInterface $msg32
     * @param PublicKeyInterface $publicKey
     * @param SignatureInterface $signature
     * @return bool
     */
    public function verify(BufferInterface $msg32, PublicKeyInterface $publicKey, SignatureInterface $signature)
    {
        /** @var PublicKey $publicKey */
        /** @var Signature $signature */
        return $this->doVerify($msg32, $publicKey, $signature);
    }

    /**
     * @param BufferInterface $msg32
     * @param CompactSignature $compactSig
     * @return PublicKey
     */
    private function doRecover(BufferInterface $msg32, CompactSignature $compactSig)
    {
        $publicKey = '';
        /** @var resource $publicKey */
        $context = $this->context;
        $sig = $compactSig->getResource();
        if (1 !== secp256k1_ecdsa_recover($context, $publicKey, $sig, $msg32->getBinary())) {
            throw new \RuntimeException('Unable to recover Public Key');
        }

        return new PublicKey($this, $publicKey, $compactSig->isCompressed());
    }

    /**
     * @param BufferInterface $msg32
     * @param CompactSignatureInterface $compactSig
     * @return PublicKey
     */
    public function recover(BufferInterface $msg32, CompactSignatureInterface $compactSig)
    {
        /** @var CompactSignature $compactSig */
        return $this->doRecover($msg32, $compactSig);
    }

    /**
     * @param BufferInterface $msg32
     * @param PrivateKey $privateKey
     * @return CompactSignature
     */
    private function doSignCompact(BufferInterface $msg32, PrivateKey $privateKey)
    {
        $sig_t = '';
        /** @var resource $sig_t */
        if (1 !== secp256k1_ecdsa_sign_recoverable($this->context, $sig_t, $msg32->getBinary(), $privateKey->getBinary())) {
            throw new \RuntimeException('Secp256k1: failed to sign');
        }

        $recid = '';
        $ser = '';
        if (!secp256k1_ecdsa_recoverable_signature_serialize_compact($this->context, $sig_t, $ser, $recid)) {
            throw new \RuntimeException('Failed to obtain recid');
        }

        unset($ser);
        return new CompactSignature(
            $this,
            $sig_t,
            $recid,
            $privateKey->isCompressed()
        );
    }

    /**
     * @param BufferInterface $msg32
     * @param PrivateKeyInterface $privateKey
     * @param RbgInterface|null $rbg
     * @return CompactSignatureInterface
     */
    public function signCompact(BufferInterface $msg32, PrivateKeyInterface $privateKey, RbgInterface $rbg = null)
    {
        /** @var PrivateKey $privateKey */
        return $this->doSignCompact($msg32, $privateKey);
    }
}
