<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Adapter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\CompactSignatureInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Buffertools\BufferInterface;

interface EcAdapterInterface
{
    /**
     * @return Math
     */
    public function getMath(): Math;

    /**
     * @return \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    public function getGenerator();

    /**
     * @param BufferInterface $buffer
     * @return bool
     */
    public function validatePrivateKey(BufferInterface $buffer): bool;

    /**
     * @param \GMP $element
     * @param bool|false $halfOrder
     * @return bool
     */
    public function validateSignatureElement(\GMP $element, bool $halfOrder = false): bool;

    /**
     * @param \GMP $scalar
     * @param bool|false $compressed
     * @return PrivateKeyInterface
     */
    public function getPrivateKey(\GMP $scalar, bool $compressed = false): PrivateKeyInterface;

    /**
     * @param BufferInterface $messageHash
     * @param PrivateKeyInterface $privateKey
     * @param RbgInterface $rbg
     * @return SignatureInterface
     */
    public function sign(BufferInterface $messageHash, PrivateKeyInterface $privateKey, RbgInterface $rbg = null): SignatureInterface;

    /**
     * @param PublicKeyInterface $publicKey
     * @param SignatureInterface $signature
     * @param BufferInterface $messageHash
     * @return bool
     */
    public function verify(BufferInterface $messageHash, PublicKeyInterface $publicKey, SignatureInterface $signature): bool;

    /**
     * @param PrivateKeyInterface $privateKey
     * @param BufferInterface $messageHash
     * @param RbgInterface $rbg
     * @return CompactSignatureInterface
     */
    public function signCompact(BufferInterface $messageHash, PrivateKeyInterface $privateKey, RbgInterface $rbg = null): CompactSignatureInterface;

    /**
     * @param BufferInterface $messageHash
     * @param CompactSignatureInterface $compactSignature
     * @return PublicKeyInterface
     */
    public function recover(BufferInterface $messageHash, CompactSignatureInterface $compactSignature): PublicKeyInterface;
}
