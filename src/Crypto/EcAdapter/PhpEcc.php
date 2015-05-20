<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Key\PrivateKey;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\PublicKey;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Signature\CompactSignature;
use BitWasp\Bitcoin\Signature\Signature;
use BitWasp\Bitcoin\Signature\SignatureInterface;
use Mdanter\Ecc\Primitives\PointInterface;

class PhpEcc extends BaseEcAdapter
{
    /**
     * @return int
     */
    public function getAdapterName()
    {
        return self::PHPECC;
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @param SignatureInterface $signature
     * @param Buffer $messageHash
     * @return bool
     */
    public function verify(Buffer $messageHash, PublicKeyInterface $publicKey, SignatureInterface $signature)
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
        $u1 = $math->mod($math->mul($messageHash->getInt(), $c), $n);
        $u2 = $math->mod($math->mul($signature->getR(), $c), $n);
        $xy = $generator->mul($u1)->add($publicKey->getPoint()->mul($u2));
        $v = $math->mod($xy->getX(), $n);

        return $math->cmp($v, $signature->getR()) == 0;
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param RbgInterface $rbg
     * @return Signature
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function sign(Buffer $messageHash, PrivateKeyInterface $privateKey, RbgInterface $rbg = null)
    {
        $rbg = $rbg ?: new Rfc6979($this, $privateKey, $messageHash);
        $randomK = $rbg->bytes(32);

        $math = $this->getMath();
        $generator = $this->getGenerator();
        $n = $generator->getOrder();

        $k = $math->mod($randomK->getInt(), $n);
        $r = $generator->mul($k)->getX();

        if ($math->cmp($r, 0) == 0) {
            throw new \RuntimeException('Random number r = 0');
        }

        $s = $math->mod(
            $math->mul(
                $math->inverseMod($k, $n),
                $math->mod(
                    $math->add(
                        $messageHash->getInt(),
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

        // if s is less than half the curve order, invert s
        if (!$this->validateSignatureElement($s, true)) {
            $s = $math->sub($n, $s);
        }

        return new Signature($r, $s);
    }

    /**
     * @param CompactSignature $signature
     * @param Buffer $messageHash
     * @return PublicKey
     * @throws \Exception
     */
    public function recoverCompact(Buffer $messageHash, CompactSignature $signature)
    {
        $math = $this->getMath();
        $G = $this->getGenerator();

        $isYEven = $math->bitwiseAnd($signature->getRecoveryId(), 1) != 0;
        $isSecondKey = $math->bitwiseAnd($signature->getRecoveryId(), 2) != 0;
        $curve = $G->getCurve();

        // Precalculate (p + 1) / 4 where p is the field order
        $p_over_four = $math->div($math->add($curve->getPrime(), 1), 4);

        // 1.1 Compute x
        if (!$isSecondKey) {
            $x = $signature->getR();
        } else {
            $x = $math->add($signature->getR(), $G->getOrder());
        }

        // 1.3 Convert x to point
        $alpha = $math->mod($math->add($math->add($math->pow($x, 3), $math->mul($curve->getA(), $x)), $curve->getB()), $curve->getPrime());
        $beta = $math->powmod($alpha, $p_over_four, $curve->getPrime());

        // If beta is even, but y isn't or vice versa, then convert it,
        // otherwise we're done and y == beta.
        if ($math->isEven($beta) == $isYEven) {
            $y = $math->sub($curve->getPrime(), $beta);
        } else {
            $y = $beta;
        }

        // 1.4 Check that nR is at infinity (implicitly done in constructor)
        $R = $G->getCurve()->getPoint($x, $y);

        $point_negate = function (PointInterface $p) use ($math, $G) {
            return $G->getCurve()->getPoint($p->getX(), $math->mul($p->getY(), -1));
        };

        // 1.6.1 Compute a candidate public key Q = r^-1 (sR - eG)
        $rInv = $math->inverseMod($signature->getR(), $G->getOrder());
        $eGNeg = $point_negate($G->mul($messageHash->getInt()));
        $Q = $R->mul($signature->getS())->add($eGNeg)->mul($rInv);

        // 1.6.2 Test Q as a public key
        $Qk = new PublicKey($this, $Q);
        if ($this->verify($messageHash, $Qk, new Signature($signature->getR(), $signature->getS()))) {
            return $Qk->setCompressed($signature->isCompressed());
        }

        throw new \Exception('Unable to recover public key');
    }

    /**
     * attempt to calculate the public key recovery param by trial and error
     *
     * @param                $r
     * @param                $s
     * @param Buffer $messageHash
     * @param PublicKeyInterface $publicKey
     * @return int
     * @throws \Exception
     */
    public function calcPubKeyRecoveryParam($r, $s, Buffer $messageHash, PublicKeyInterface $publicKey)
    {
        $Q = $publicKey->getPoint();
        for ($i = 0; $i < 4; $i++) {
            try {
                $test = new CompactSignature($r, $s, $i, $publicKey->isCompressed());
                if ($this->recoverCompact($messageHash, $test)->getPoint()->equals($Q)) {
                    return $i;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new \Exception("Failed to find valid recovery factor");
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param RbgInterface $rbg
     * @return CompactSignature
     */
    public function signCompact(Buffer $messageHash, PrivateKeyInterface $privateKey, RbgInterface $rbg = null)
    {
        $sign = $this->sign($messageHash, $privateKey, $rbg);

        // calculate the recovery param
        // there should be a way to get this when signing too, but idk how ...
        return new CompactSignature(
            $sign->getR(),
            $sign->getS(),
            $this->calcPubKeyRecoveryParam($sign->getR(), $sign->getS(), $messageHash, $privateKey->getPublicKey()),
            $privateKey->isCompressed()
        );
    }

    /**
     * @param Buffer $privateKey
     * @return bool
     */
    public function validatePrivateKey(Buffer $privateKey)
    {
        return $this->checkInt($privateKey->getInt(), $this->getGenerator()->getOrder());
    }

    /**
     * @param Buffer $publicKey
     * @return bool
     */
    public function validatePublicKey(Buffer $publicKey)
    {
        if (PublicKey::isCompressedOrUncompressed($publicKey)) {
            try {
                $this->publicKeyFromBuffer($publicKey);
                return true;
            } catch (\Exception $e) {
                // Let the function finish and return false
            }
        }

        return false;
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @return \BitWasp\Bitcoin\Key\PublicKey
     */
    public function privateToPublic(PrivateKeyInterface $privateKey)
    {
        return new PublicKey(
            $this,
            $this->getGenerator()->mul($privateKey->getSecretMultiplier()),
            $privateKey->isCompressed()
        );
    }

    /**
     * @param PrivateKeyInterface $oldPrivate
     * @param $newSecret
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     */
    private function getRelatedPrivateKey(PrivateKeyInterface $oldPrivate, $newSecret)
    {
        return new PrivateKey(
            $this,
            $newSecret,
            $oldPrivate->isCompressed()
        );
    }

    /**
     * @param PublicKeyInterface $oldPublic
     * @param PointInterface $newPoint
     * @return \BitWasp\Bitcoin\Key\PublicKey
     */
    private function getRelatedPublicKey(PublicKeyInterface $oldPublic, PointInterface $newPoint)
    {
        return new PublicKey(
            $this,
            $newPoint,
            $oldPublic->isCompressed()
        );
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param $integer
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     */
    public function privateKeyAdd(PrivateKeyInterface $privateKey, $integer)
    {
        $math = $this->getMath();
        return $this->getRelatedPrivateKey(
            $privateKey,
            $math->mod(
                $math->add(
                    $integer,
                    $privateKey->getSecretMultiplier()
                ),
                $this->getGenerator()->getOrder()
            )
        );
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param $integer
     * @return \BitWasp\Bitcoin\Key\PrivateKey
     */
    public function privateKeyMul(PrivateKeyInterface $privateKey, $integer)
    {
        $math = $this->getMath();

        return $this->getRelatedPrivateKey(
            $privateKey,
            $math->mod(
                $math->mul(
                    $integer,
                    $privateKey->getSecretMultiplier()
                ),
                $this->getGenerator()->getOrder()
            )
        );
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
        return $this->getRelatedPublicKey(
            $publicKey,
            $publicKey->getPoint()->add($this->getGenerator()->mul($integer))
        );
    }
}
