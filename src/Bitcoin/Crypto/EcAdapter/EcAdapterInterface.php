<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Signature\CompactSignature;
use BitWasp\Bitcoin\Signature\SignatureCollection;
use BitWasp\Bitcoin\Signature\SignatureInterface;

interface EcAdapterInterface
{
    /**
     * @return \BitWasp\Bitcoin\Math\Math
     */
    public function getMath();

    /**
     * @return \Mdanter\Ecc\GeneratorPoint
     */
    public function getGenerator();

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
     * @param SignatureCollection $signatures
     * @param Buffer $messageHash
     * @param PublicKeyInterface[] $publicKeys
     * @return SignatureInterface[]
     */
    public function associateSigs(SignatureCollection $signatures, Buffer $messageHash, array $publicKeys);

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
     * @param CompactSignature $compactSignature
     * @param Buffer $messageHash
     * @param PayToPubKeyHashAddress $address
     * @return bool
     */
    public function verifyMessage(Buffer $messageHash, PayToPubKeyHashAddress $address, CompactSignature $compactSignature);

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
     * @param $integer
     * @return PublicKeyInterface
     */
    public function publicKeyAdd(PublicKeyInterface $publicKey, $integer);

    /**
     * @param PublicKeyInterface $publicKey
     * @param $integer
     * @return PublicKeyInterface
     */
    public function publicKeyMul(PublicKeyInterface $publicKey, $integer);

    /**
     * @param PrivateKeyInterface $publicKey
     * @param $integer
     * @return PrivateKeyInterface
     */
    public function privateKeyAdd(PrivateKeyInterface $publicKey, $integer);

    /**
     * @param PrivateKeyInterface $publicKey
     * @param $integer
     * @return PrivateKeyInterface
     */
    public function privateKeyMul(PrivateKeyInterface $publicKey, $integer);
}
