<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Signature\Signature;
use BitWasp\Bitcoin\Signature\SignatureInterface;
use Mdanter\Ecc\PointInterface;

class PhpEcc extends BaseEcAdapter
{

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

        $math = $this->getMath();
        $generator = $this->getGenerator();
        $n = $generator->getOrder();

        $k = $math->mod($randomK->serialize('int'), $n);
        $r = $generator->mul($k)->getX();

        if ($math->cmp($r, 0) == 0) {
            throw new \RuntimeException('Random number r = 0');
        }

        $s = $math->mod(
            $math->mul(
                $math->inverseMod($k, $n),
                $math->mod(
                    $math->add(
                        $messageHash->serialize('int'),
                        $math->mul(
                            $privateKey->getSecretMultiplier(),
                            $r
                        )
                    ),
                    $n
                )
            ),
            $n
        );

        if ($math->cmp($s, 0) == 0) {
            throw new \RuntimeException('Signature s = 0');
        }

        // if s < n/2
        if ($math->cmp($s, $math->div($n, 2)) > 0) {
            $s = $math->sub($n, $s);
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
        $n = $this->getGenerator()->getOrder();
        $math = $this->getMath();
        $generator = $this->getGenerator();

        if ($math->cmp($signature->getR(), 1) < 1 || $math->cmp($signature->getR(), $math->sub($n, 1)) > 0) {
            return false;
        }

        if ($math->cmp($signature->getS(), 1) < 1 || $math->cmp($signature->getS(), $math->sub($n, 1)) > 0) {
            return false;
        }

        $c = $math->inverseMod($signature->getS(), $n);
        $u1 = $math->mod($math->mul($messageHash->serialize('int'), $c), $n);
        $u2 = $math->mod($math->mul($signature->getR(), $c), $n);
        $xy = $generator->mul($u1)->add($publicKey->getPoint()->mul($u2));
        $v = $math->mod($xy->getX(), $n);

        return $math->cmp($v, $signature->getR()) == 0;
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @return \BitWasp\Bitcoin\Key\PublicKey
     */
    public function privateToPublic(PrivateKeyInterface $privateKey)
    {
        $point = $this->getGenerator()->mul($privateKey->getSecretMultiplier());
        return PublicKeyFactory::fromPoint($point, $privateKey->isCompressed(), $this);
    }

    /**
     * @param PrivateKeyInterface $oldPrivate
     * @param $newSecret
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     */
    private function getRelatedPrivateKey(PrivateKeyInterface $oldPrivate, $newSecret)
    {
        return PrivateKeyFactory::fromInt($newSecret, $oldPrivate->isCompressed(), $this);
    }

    /**
     * @param PublicKeyInterface $oldPublic
     * @param PointInterface $newPoint
     * @return \BitWasp\Bitcoin\Key\PublicKey
     */
    private function getRelatedPublicKey(PublicKeyInterface $oldPublic, PointInterface $newPoint)
    {
        return PublicKeyFactory::fromPoint($newPoint, $oldPublic->isCompressed(), $this);
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param $integer
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     */
    public function privateKeyAdd(PrivateKeyInterface $privateKey, $integer)
    {
        $math = $this->getMath();
        $newSecret = $math->mod(
            $math->add(
                $integer,
                $privateKey->getSecretMultiplier()
            ),
            $this->getGenerator()->getOrder()
        );

        return $this->getRelatedPrivateKey($privateKey, $newSecret);
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param $integer
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     */
    public function privateKeyMul(PrivateKeyInterface $privateKey, $integer)
    {
        $math = $this->getMath();
        $newSecret = $math->mod(
            $math->mul(
                $integer,
                $privateKey->getSecretMultiplier()
            ),
            $this->getGenerator()->getOrder()
        );

        return $this->getRelatedPrivateKey($privateKey, $newSecret);
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @param $integer
     * @return \BitWasp\Bitcoin\Key\PublicKey
     */
    public function publicKeyMul(PublicKeyInterface $publicKey, $integer)
    {
        $newPoint = $publicKey->getPoint()->mul($integer);
        return $this->getRelatedPublicKey($publicKey, $newPoint);
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @param $integer
     * @return \BitWasp\Bitcoin\Key\PublicKey
     */
    public function publicKeyAdd(PublicKeyInterface $publicKey, $integer)
    {
        $newPoint = $publicKey->getPoint()->add($this->getGenerator()->mul($integer));
        return $this->getRelatedPublicKey($publicKey, $newPoint);
    }
}
