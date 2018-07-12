<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key\PrivateKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key\PublicKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature\CompactSignature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\CompactSignatureInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Buffertools\BufferInterface;
use Mdanter\Ecc\Primitives\GeneratorPoint;

class EcAdapter implements EcAdapterInterface
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var \GMP
     */
    private $order;

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
        $this->order = $generator->getOrder();
        $this->context = $secp256k1_context_t;
    }

    /**
     * @return Math
     */
    public function getMath(): Math
    {
        return $this->math;
    }

    /**
     * @return \GMP
     */
    public function getOrder(): \GMP
    {
        return $this->order;
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
    public function validatePrivateKey(BufferInterface $privateKey): bool
    {
        return (bool) secp256k1_ec_seckey_verify($this->context, $privateKey->getBinary());
    }

    /**
     * @param \GMP $element
     * @param bool $half
     * @return bool
     */
    public function validateSignatureElement(\GMP $element, bool $half = false): bool
    {
        $math = $this->getMath();
        $against = $this->getOrder();
        if ($half) {
            $against = $math->rightShift($against, 1);
        }

        return $math->cmp($element, $against) < 0 && $math->cmp($element, gmp_init(0)) !== 0;
    }

    /**
     * @param \GMP $int
     * @param bool $compressed
     * @return PrivateKeyInterface
     */
    public function getPrivateKey(\GMP $int, bool $compressed = false): PrivateKeyInterface
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
     * @param CompactSignature $compactSig
     * @return PublicKey
     */
    private function doRecover(BufferInterface $msg32, CompactSignature $compactSig): PublicKey
    {
        $publicKey = null;
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
     * @return PublicKeyInterface
     */
    public function recover(BufferInterface $msg32, CompactSignatureInterface $compactSig): PublicKeyInterface
    {
        /** @var CompactSignature $compactSig */
        return $this->doRecover($msg32, $compactSig);
    }
}
