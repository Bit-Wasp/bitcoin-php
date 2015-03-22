<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;


use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Signature\Signature;
use BitWasp\Bitcoin\Signature\SignatureInterface;
use Mdanter\Ecc\GeneratorPoint;
use Mdanter\Ecc\PointInterface;

class PhpEcc implements EcAdapterInterface
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var GeneratorPoint
     */
    private $generator;

    /**
     * @param Math $math
     * @param GeneratorPoint $G
     */
    public function __construct(Math $math, GeneratorPoint $G)
    {
        $this->math = $math;
        $this->generator = $G;
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param RbgInterface $rbg
     * @return Signature
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function sign(PrivateKeyInterface $privateKey, Buffer $messageHash, RbgInterface $rbg = null)
    {
        $rbg = $rbg ?: new Random();
        $randomK = $rbg->bytes(32);

        $n       = $this->generator->getOrder();
        $k       = $this->math->mod($randomK->serialize('int'), $n);
        $r       = $this->generator->mul($k)->getX();

        if ($this->math->cmp($r, 0) == 0) {
            throw new \RuntimeException('Random number r = 0');
        }

        $s = $this->math->mod(
            $this->math->mul(
                $this->math->inverseMod($k, $n),
                $this->math->mod(
                    $this->math->add(
                        $messageHash->serialize('int'),
                        $this->math->mul(
                            $privateKey->getSecretMultiplier(),
                            $r
                        )
                    ),
                    $n
                )
            ),
            $n
        );

        if ($this->math->cmp($s, 0) == 0) {
            throw new \RuntimeException('Signature s = 0');
        }

        // if s < n/2
        if ($this->math->cmp($s, $this->math->div($n, 2)) > 0) {
            $s = $this->math->sub($n, $s);
        }

        return new Signature($r, $s);
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @param SignatureInterface $signature
     * @param Buffer $messageHash
     * @return bool
     */
    public function verify(PublicKeyInterface $publicKey, SignatureInterface $signature, Buffer $messageHash)
    {
        $n = $this->generator->getOrder();
        $point = $publicKey->getPoint();
        $r = $signature->getR();
        $s = $signature->getS();

        if ($this->math->cmp($r, 1) < 1 || $this->math->cmp($r, $this->math->sub($n, 1)) > 0) {
            return false;
        }

        if ($this->math->cmp($s, 1) < 1 || $this->math->cmp($s, $this->math->sub($n, 1)) > 0) {
            return false;
        }

        $c = $this->math->inverseMod($s, $n);
        $u1 = $this->math->mod($this->math->mul($messageHash->serialize('int'), $c), $n);
        $u2 = $this->math->mod($this->math->mul($r, $c), $n);
        $xy = $this->generator->mul($u1)->add($point->mul($u2));
        $v = $this->math->mod($xy->getX(), $n);

        return $this->math->cmp($v, $r) == 0;
    }

    /**
     * @param PrivateKeyInterface $oldPrivate
     * @param $newSecret
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     */
    private function getRelatedPrivateKey(PrivateKeyInterface $oldPrivate, $newSecret)
    {
        return PrivateKeyFactory::fromInt($newSecret, $oldPrivate->isCompressed(), $this->math, $this->generator);
    }

    /**
     * @param PublicKeyInterface $oldPublic
     * @param PointInterface $newPoint
     * @return \BitWasp\Bitcoin\Key\PublicKey
     */
    private function getRelatedPublicKey(PublicKeyInterface $oldPublic, PointInterface $newPoint)
    {
        return PublicKeyFactory::fromPoint($newPoint, $oldPublic->isCompressed(), $this->math, $this->generator);
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param $scalar
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     */
    public function privateKeyAdd(PrivateKeyInterface $privateKey, $scalar)
    {
        $newSecret = $this->math->mod(
            $this->math->add(
                $scalar,
                $privateKey->getSecretMultiplier()
            ),
            $this->generator->getOrder()
        );

        return $this->getRelatedPrivateKey($privateKey, $newSecret);
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param $scalar
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     */
    public function privateKeyMul(PrivateKeyInterface $privateKey, $scalar)
    {
        $newSecret = $this->math->mod(
            $this->math->mul(
                $scalar,
                $privateKey->getSecretMultiplier()
            ),
            $this->generator->getOrder()
        );

        return $this->getRelatedPrivateKey($privateKey, $newSecret);
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @param $scalar
     * @return \BitWasp\Bitcoin\Key\PublicKey
     */
    public function publicKeyMul(PublicKeyInterface $publicKey, $scalar)
    {
        $newPoint = $publicKey->getPoint()->mul($scalar);
        return $this->getRelatedPublicKey($publicKey, $newPoint);
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @param $scalar
     * @return \BitWasp\Bitcoin\Key\PublicKey
     */
    public function publicKeyAdd(PublicKeyInterface $publicKey, $scalar)
    {
        $newPoint = $publicKey->getPoint()->add($this->generator->mul($scalar));
        return $this->getRelatedPublicKey($publicKey, $newPoint);
    }
}