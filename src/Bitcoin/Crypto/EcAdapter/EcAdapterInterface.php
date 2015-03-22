<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;


use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Signature\SignatureInterface;

interface EcAdapterInterface
{

    /**
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param RbgInterface $rbg
     * @return SignatureInterface
     */
    public function sign(PrivateKeyInterface $privateKey, Buffer $messageHash, RbgInterface $rbg = null);

    /**
     * @param PublicKeyInterface $publicKey
     * @param SignatureInterface $signature
     * @param Buffer $messageHash
     * @return bool
     */
    public function verify(PublicKeyInterface $publicKey, SignatureInterface $signature, Buffer $messageHash);

    /**
     * @param PublicKeyInterface $publicKey
     * @param $scalar
     * @return PublicKeyInterface
     */
    public function publicKeyAdd(PublicKeyInterface $publicKey, $scalar);

    /**
     * @param PublicKeyInterface $publicKey
     * @param $scalar
     * @return PublicKeyInterface
     */
    public function publicKeyMul(PublicKeyInterface $publicKey, $scalar);

    /**
     * @param PrivateKeyInterface $publicKey
     * @param $scalar
     * @return PrivateKeyInterface
     */
    public function privateKeyAdd(PrivateKeyInterface $publicKey, $scalar);

    /**
     * @param PrivateKeyInterface $publicKey
     * @param $scalar
     * @return PrivateKeyInterface
     */
    public function privateKeyMul(PrivateKeyInterface $publicKey, $scalar);
}