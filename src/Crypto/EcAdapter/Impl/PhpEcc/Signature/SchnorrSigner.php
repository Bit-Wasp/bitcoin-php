<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PrivateKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Buffertools\BufferInterface;

class SchnorrSigner
{
    /**
     * @var EcAdapter
     */
    private $adapter;

    public function __construct(EcAdapter $ecAdapter)
    {
        $this->adapter = $ecAdapter;
    }

    /**
     * @param PrivateKey $privateKey
     * @param BufferInterface $message32
     * @return Signature
     */
    public function sign(PrivateKey $privateKey, BufferInterface $message32): Signature
    {
        $G = $this->adapter->getGenerator();
        $n = $G->getOrder();
        $k = $this->hashPrivateData($privateKey, $message32, $n);
        $R = $G->mul($k);

        if (gmp_cmp(gmp_jacobi($R->getY(), $G->getCurve()->getPrime()), 1) !== 0) {
            $k = gmp_sub($G->getOrder(), $k);
        }

        $e = $this->hashPublicData($R->getX(), $privateKey->getPublicKey(), $message32, $n);
        $s = gmp_mod(gmp_add($k, gmp_mod(gmp_mul($e, $privateKey->getSecret()), $n)), $n);
        return new Signature($this->adapter, $R->getX(), $s);
    }

    /**
     * @param \GMP $n
     * @return string
     */
    private function tob32(\GMP $n): string
    {
        return $this->adapter->getMath()->intToFixedSizeString($n, 32);
    }

    /**
     * @param PrivateKey $privateKey
     * @param BufferInterface $message32
     * @param \GMP $n
     * @return \GMP
     */
    private function hashPrivateData(PrivateKey $privateKey, BufferInterface $message32, \GMP $n): \GMP
    {
        $hasher = hash_init('sha256');
        hash_update($hasher, $this->tob32($privateKey->getSecret()));
        hash_update($hasher, $message32->getBinary());
        return gmp_mod(gmp_init(hash_final($hasher, false), 16), $n);
    }

    /**
     * @param \GMP $Rx
     * @param PublicKey $publicKey
     * @param BufferInterface $message32
     * @param \GMP $n
     * @param string|null $rxBytes
     * @return \GMP
     */
    private function hashPublicData(\GMP $Rx, PublicKey $publicKey, BufferInterface $message32, \GMP $n, string &$rxBytes = null): \GMP
    {
        $hasher = hash_init('sha256');
        $rxBytes = $this->tob32($Rx);
        hash_update($hasher, $rxBytes);
        hash_update($hasher, $publicKey->getBinary());
        hash_update($hasher, $message32->getBinary());
        return gmp_mod(gmp_init(hash_final($hasher, false), 16), $n);
    }

    public function verify(BufferInterface $message32, PublicKey $publicKey, Signature $signature): bool
    {
        $G = $this->adapter->getGenerator();
        $n = $G->getOrder();
        $p = $G->getCurve()->getPrime();

        if (gmp_cmp($signature->getR(), $p) >= 0 || gmp_cmp($signature->getR(), $n) >= 0) {
            return false;
        }

        $RxBytes = null;
        $e = $this->hashPublicData($signature->getR(), $publicKey, $message32, $n, $RxBytes);
        $R = $G->mul($signature->getS())->add($publicKey->tweakMul(gmp_sub($G->getOrder(), $e))->getPoint());

        $jacobiNotOne = gmp_cmp(gmp_jacobi($R->getY(), $p), 1) !== 0;
        $rxNotEquals = !hash_equals($RxBytes, $this->tob32($R->getX()));
        if ($jacobiNotOne || $rxNotEquals) {
            return false;
        }
        return true;
    }
}
