<?php
/**
 * Created by PhpStorm.
 * User: aeonium
 * Date: 05/08/15
 * Time: 00:55
 */

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Adapter;


use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Signature\CompactSignature;
use BitWasp\Buffertools\Buffer;

class EcAdapter implements EcAdapterInterface
{
    /**
     * @var EcAdapterInterface
     */
    private $adapter;

    /**
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(EcAdapterInterface $ecAdapter)
    {
        $this->adapter = $ecAdapter;
    }

    /**
     * @return \BitWasp\Bitcoin\Math\Math
     */
    public function getMath()
    {
        return $this->adapter->getMath();
    }

    /**
     * @return \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    public function getGenerator()
    {
        return $this->adapter->getGenerator();
    }

    /**
     * @param Buffer $msg32
     * @param PrivateKeyInterface $privateKey
     * @param RbgInterface|null $rbg
     * @return SignatureInterface
     */
    public function sign(Buffer $msg32, PrivateKeyInterface $privateKey, RbgInterface $rbg = null)
    {
        return $this->adapter->sign($msg32, $privateKey, $rbg);
    }

    /**
     * @param Buffer $msg32
     * @param PublicKeyInterface $publicKey
     * @param SignatureInterface $signature
     * @return bool
     */
    public function verify(Buffer $msg32, PublicKeyInterface $publicKey, SignatureInterface $signature)
    {
        return $this->adapter->verify($msg32, $publicKey, $signature);
    }

    /**
     * @param Buffer $msg32
     * @param CompactSignature $compactSig
     * @return PublicKeyInterface
     */
    public function recover(Buffer $msg32, CompactSignature $compactSig)
    {
        return $this->adapter->recover($msg32, $compactSig);
    }

    /**
     * @param Buffer $msg32
     * @param PrivateKeyInterface $privateKey
     * @param RbgInterface|null $rbg
     * @return CompactSignature
     */
    public function signCompact(Buffer $msg32, PrivateKeyInterface $privateKey, RbgInterface $rbg = null)
    {
        return $this->adapter->signCompact($msg32, $privateKey, $rbg);
    }
}