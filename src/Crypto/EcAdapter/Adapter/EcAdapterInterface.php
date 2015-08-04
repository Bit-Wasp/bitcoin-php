<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Adapter;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Bitcoin\Signature\CompactSignature;

interface EcAdapterInterface
{

    /**
     * @return \BitWasp\Bitcoin\Math\Math
     */
    public function getMath();

    /**
     * @return \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    public function getGenerator();

    /**
     * @param Buffer $messageHash
     * @param PrivateKeyInterface $privateKey
     * @param RbgInterface $rbg
     * @return SignatureInterface
     */
    public function sign($messageHash, $privateKey, $rbg = null);

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
     * @param Buffer $messageHash
     * @param CompactSignature $compactSignature
     * @return PublicKeyInterface
     */
    public function recover(Buffer $messageHash, CompactSignature $compactSignature);

}
