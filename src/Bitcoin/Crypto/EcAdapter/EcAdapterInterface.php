<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
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
    public function sign(PrivateKeyInterface $privateKey, Buffer $messageHash, RbgInterface $rbg = null);

    /**
     * @param PublicKeyInterface $publicKey
     * @param SignatureInterface $signature
     * @param Buffer $messageHash
     * @return bool
     */
    public function verify(PublicKeyInterface $publicKey, SignatureInterface $signature, Buffer $messageHash);

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
