<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Signature\CompactSignature;
use BitWasp\Bitcoin\Signature\SignatureInterface;

interface EcAdapterInterface
{
    const PHPECC = 0;
    const SECP256K1 = 1;

    /**
     * @return string
     */
    public function getAdapterName();

    /**
     * @return \BitWasp\Bitcoin\Math\Math
     */
    public function getMath();

    /**
     * @return \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    public function getGenerator();

    /**
     * @return int|string
     */
    public function halfOrder();

    /**
     * @param int|string $int
     * @param int|string $max
     * @return bool
     */
    public function checkInt($int, $max);

    /**
     * @param int $element
     * @param bool $half
     * @return bool
     */
    public function validateSignatureElement($element, $half);

    /**
     * @param Buffer $publicKey
     * @return \BitWasp\Bitcoin\Key\PublicKey
     */
    public function publicKeyFromBuffer(Buffer $publicKey);

    /**
     * @param integer $xCoord
     * @param string $prefix
     * @return integer
     */
    public function recoverYfromX($xCoord, $prefix);

    /**
     * @param array $signatures
     * @param Buffer $messageHash
     * @param PublicKeyInterface[] $publicKeys
     * @return SignatureInterface[]
     */
    public function associateSigs(array $signatures, Buffer $messageHash, array $publicKeys);

    /**
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param RbgInterface $rbg
     * @return SignatureInterface
     */
    public function sign(Buffer $messageHash, PrivateKeyInterface $privateKey, RbgInterface $rbg = null);

    /**
     * @param PublicKeyInterface $publicKey
     * @param SignatureInterface $signature
     * @param Buffer $messageHash
     * @return bool
     */
    public function verify(Buffer $messageHash, PublicKeyInterface $publicKey, SignatureInterface $signature);

    /**
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param RbgInterface $rbg
     * @return CompactSignature
     */
    public function signCompact(Buffer $messageHash, PrivateKeyInterface $privateKey, RbgInterface $rbg = null);

    /**
     * @param CompactSignature $compactSignature
     * @param Buffer $messageHash
     * @return PublicKeyInterface
     */
    public function recoverCompact(Buffer $messageHash, CompactSignature $compactSignature);

    /**
     * @param Buffer $privateKey
     * @return bool
     */
    public function validatePrivateKey(Buffer $privateKey);

    /**
     * @param Buffer $publicKey
     * @return bool
     */
    public function validatePublicKey(Buffer $publicKey);

    /**
     * @param PrivateKeyInterface $privateKey
     * @return PublicKeyInterface
     */
    public function privateToPublic(PrivateKeyInterface $privateKey);

    /**
     * @param PublicKeyInterface $publicKey
     * @param integer $integer
     * @return PublicKeyInterface
     */
    public function publicKeyAdd(PublicKeyInterface $publicKey, $integer);

    /**
     * @param PublicKeyInterface $publicKey
     * @param integer $integer
     * @return PublicKeyInterface
     */
    public function publicKeyMul(PublicKeyInterface $publicKey, $integer);

    /**
     * @param PrivateKeyInterface $privateKey
     * @param integer $integer
     * @return PrivateKeyInterface
     */
    public function privateKeyAdd(PrivateKeyInterface $privateKey, $integer);

    /**
     * @param PrivateKeyInterface $privateKey
     * @param integer $integer
     * @return PrivateKeyInterface
     */
    public function privateKeyMul(PrivateKeyInterface $privateKey, $integer);
}
