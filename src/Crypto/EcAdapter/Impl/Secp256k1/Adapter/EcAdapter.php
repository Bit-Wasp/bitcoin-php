<?php
/**
 * Created by PhpStorm.
 * User: aeonium
 * Date: 03/08/15
 * Time: 18:03
 */

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter;


use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key\PublicKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Key\PrivateKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature\Signature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Signature\CompactSignature;
use BitWasp\Buffertools\Buffer;
use Mdanter\Ecc\Primitives\GeneratorPoint;

class EcAdapter implements EcAdapterInterface
{
    private $math;
    private $generator;
    private $context;

    public function __construct(Math $math, GeneratorPoint $generator, $secp256k1_context_t)
    {
        if (!is_resource($secp256k1_context_t) || !get_resource_type($secp256k1_context_t) == SECP256K1_TYPE_CONTEXT) {
            throw new \InvalidArgumentException('Secp256k1: Must pass a secp256k1_context_t resource');
        }
        $this->math = $math;
        $this->generator = $generator;
        $this->context = $secp256k1_context_t;
    }

    public function getMath()
    {
        return $this->math;
    }

    public function getGenerator()
    {
        return $this->generator;
    }

    public function getPrivateKey($int, $compressed = false)
    {
        return new PrivateKey($this, $int, $compressed);
    }

    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param Buffer $msg32
     * @param PrivateKey $privateKey
     * @param RbgInterface|null $rbgInterface
     * @return Signature
     */
    public function sign(Buffer $msg32, PrivateKeyInterface $privateKey, RbgInterface $rbgInterface = null)
    {
        $sig_t = '';
        if (1 !== secp256k1_ecdsa_sign($this->context, $msg32->getBinary(), $privateKey->getBinary(), $sig_t)) {
            throw new \RuntimeException('Secp256k1: failed to sign');
        }

        return new Signature($this, $sig_t);
    }

    /**
     * @param Buffer $msg32
     * @param Signature $signature
     * @param PublicKey $publicKey
     * @return bool
     */
    public function verify(Buffer $msg32, PublicKey $publicKey, Signature $signature)
    {
        return (bool) secp256k1_ecdsa_verify($this->context, $msg32->getBinary(), $signature->getResource(), $publicKey->getResource());
    }

    /**
     * @param Buffer $msg32
     * @param CompactSignature $compactSig
     */
    public function recover(Buffer $msg32, CompactSignature $compactSig)
    {
        $context = $this->context;
        $sig = $compactSig->
        secp256k1_ecdsa_recover($context, $msg32->getBinary());
    }


}