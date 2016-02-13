<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\CompactSignatureInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature\Signature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PrivateKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature\CompactSignature;
use BitWasp\Buffertools\BufferInterface;
use Mdanter\Ecc\Primitives\GeneratorPoint;
use Mdanter\Ecc\Primitives\PointInterface;

class EcAdapter implements EcAdapterInterface
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
     * @param GeneratorPoint $generator
     */
    public function __construct(Math $math, GeneratorPoint $generator)
    {
        $this->math = $math;
        $this->generator = $generator;
    }

    /**
     * @return Math
     */
    public function getMath()
    {
        return $this->math;
    }

    /**
     * @return GeneratorPoint
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * @param int|string $scalar
     * @param bool|false $compressed
     * @return PrivateKey
     */
    public function getPrivateKey($scalar, $compressed = false)
    {
        return new PrivateKey($this, $scalar, $compressed);
    }

    /**
     * @param PointInterface $point
     * @param bool|false $compressed
     * @return PublicKey
     */
    public function getPublicKey(PointInterface $point, $compressed = false)
    {
        return new PublicKey($this, $point, $compressed);
    }

    /**
     * @param int|string $r
     * @param int|string $s
     * @return Signature
     */
    public function getSignature($r, $s)
    {
        return new Signature($this, $r, $s);
    }

    /**
     * @param BufferInterface $messageHash
     * @param PublicKey $publicKey
     * @param Signature $signature
     * @return bool
     */
    private function doVerify(BufferInterface $messageHash, PublicKey $publicKey, Signature $signature)
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

        return $math->cmp($v, $signature->getR()) === 0;
    }

    /**
     * @param BufferInterface $messageHash
     * @param PublicKeyInterface $publicKey
     * @param SignatureInterface $signature
     * @return bool
     */
    public function verify(BufferInterface $messageHash, PublicKeyInterface $publicKey, SignatureInterface $signature)
    {
        /** @var PublicKey $publicKey */
        /** @var Signature $signature */
        return $this->doVerify($messageHash, $publicKey, $signature);
    }

    /**
     * @param BufferInterface $messageHash
     * @param PrivateKey $privateKey
     * @param RbgInterface|null $rbg
     * @return Signature
     */
    private function doSign(BufferInterface $messageHash, PrivateKey $privateKey, RbgInterface $rbg = null)
    {
        $rbg = $rbg ?: new Rfc6979($this, $privateKey, $messageHash);
        $randomK = $rbg->bytes(32);

        $math = $this->getMath();
        $generator = $this->getGenerator();
        $n = $generator->getOrder();

        $k = $math->mod($randomK->getInt(), $n);
        $r = $generator->mul($k)->getX();

        if ($math->cmp($r, 0) === 0) {
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

        if ($math->cmp($s, 0) === 0) {
            throw new \RuntimeException('Signature s = 0');
        }

        // if s is less than half the curve order, invert s
        if (!$this->validateSignatureElement($s, true)) {
            $s = $math->sub($n, $s);
        }

        return new Signature($this, $r, $s);
    }

    /**
     * @param BufferInterface $messageHash
     * @param PrivateKeyInterface $privateKey
     * @param RbgInterface $rbg
     * @return SignatureInterface
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function sign(BufferInterface $messageHash, PrivateKeyInterface $privateKey, RbgInterface $rbg = null)
    {
        /** @var PrivateKey $privateKey */
        return $this->doSign($messageHash, $privateKey, $rbg);
    }

    /**
     * @param BufferInterface $messageHash
     * @param CompactSignatureInterface $signature
     * @return PublicKey
     * @throws \Exception
     */
    public function recover(BufferInterface $messageHash, CompactSignatureInterface $signature)
    {
        $math = $this->getMath();
        $G = $this->getGenerator();

        $isYEven = $math->cmp($math->bitwiseAnd($signature->getRecoveryId(), 1), 0) !== 0;
        $isSecondKey = $math->cmp($math->bitwiseAnd($signature->getRecoveryId(), 2), 0) !== 0;
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
        if ($math->isEven($beta) === $isYEven) {
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
        $Qk = new PublicKey($this, $Q, $signature->isCompressed());
        if ($this->verify($messageHash, $Qk, $signature->convert())) {
            return $Qk;
        }

        throw new \Exception('Unable to recover public key');
    }

    /**
     * Attempt to calculate the public key recovery param by trial and error
     *
     * @param integer|string $r
     * @param integer|string $s
     * @param BufferInterface $messageHash
     * @param PublicKey $publicKey
     * @return int
     * @throws \Exception
     */
    public function calcPubKeyRecoveryParam($r, $s, BufferInterface $messageHash, PublicKey $publicKey)
    {
        $Q = $publicKey->getPoint();
        for ($i = 0; $i < 4; $i++) {
            try {
                $recover = $this->recover($messageHash, new CompactSignature($this, $r, $s, $i, $publicKey->isCompressed()));
                if ($recover->getPoint()->equals($Q)) {
                    return $i;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new \Exception('Failed to find valid recovery factor');
    }

    /**
     * @param BufferInterface $messageHash
     * @param PrivateKey $privateKey
     * @param RbgInterface|null $rbg
     * @return CompactSignature
     * @throws \Exception
     */
    private function doSignCompact(BufferInterface $messageHash, PrivateKey $privateKey, RbgInterface $rbg = null)
    {
        $sign = $this->sign($messageHash, $privateKey, $rbg);

        // calculate the recovery param
        // there should be a way to get this when signing too, but idk how ...
        return new CompactSignature(
            $this,
            $sign->getR(),
            $sign->getS(),
            $this->calcPubKeyRecoveryParam($sign->getR(), $sign->getS(), $messageHash, $privateKey->getPublicKey()),
            $privateKey->isCompressed()
        );
    }

    /**
     * @param PrivateKeyInterface $privateKey
     * @param BufferInterface $messageHash
     * @param RbgInterface $rbg
     * @return CompactSignature
     */
    public function signCompact(BufferInterface $messageHash, PrivateKeyInterface $privateKey, RbgInterface $rbg = null)
    {
        /** @var PrivateKey $privateKey */
        return $this->doSignCompact($messageHash, $privateKey, $rbg);
    }

    /**
     * @param BufferInterface $privateKey
     * @return bool
     */
    public function validatePrivateKey(BufferInterface $privateKey)
    {
        $math = $this->math;
        $scalar = $privateKey->getInt();
        return $math->cmp($scalar, 0) > 0 && $math->cmp($scalar, $this->getGenerator()->getOrder()) < 0;
    }

    /**
     * @param int|string $element
     * @param bool $half
     * @return bool
     */
    public function validateSignatureElement($element, $half = false)
    {
        $math = $this->getMath();
        $against = $this->getGenerator()->getOrder();
        if ($half) {
            $against = $math->rightShift($against, 1);
        }

        return $math->cmp($element, $against) < 0 && $math->cmp($element, 0) !== 0;
    }

    /**
     * @param BufferInterface $publicKey
     * @return PublicKeyInterface
     * @throws \Exception
     */
    public function publicKeyFromBuffer(BufferInterface $publicKey)
    {
        $compressed = $publicKey->getSize() == PublicKey::LENGTH_COMPRESSED;
        $xCoord = $publicKey->slice(1, 32)->getInt();

        return new PublicKey(
            $this,
            $this->getGenerator()
                ->getCurve()
                ->getPoint(
                    $xCoord,
                    $compressed
                    ? $this->recoverYfromX($xCoord, $publicKey->slice(0, 1)->getHex())
                    : $publicKey->slice(33, 32)->getInt()
                ),
            $compressed
        );
    }

    /**
     * @param int|string $xCoord
     * @param string $prefix
     * @return int|string
     * @throws \Exception
     */
    public function recoverYfromX($xCoord, $prefix)
    {
        if (!in_array($prefix, array(PublicKey::KEY_COMPRESSED_ODD, PublicKey::KEY_COMPRESSED_EVEN))) {
            throw new \RuntimeException('Incorrect byte for a public key');
        }

        $math = $this->getMath();
        $curve = $this->getGenerator()->getCurve();
        $prime = $curve->getPrime();

        // Calculate first root
        $root0 = $math->getNumberTheory()->squareRootModP(
            $math->add(
                $math->powMod(
                    $xCoord,
                    3,
                    $prime
                ),
                $curve->getB()
            ),
            $prime
        );

        // Depending on the byte, we expect the Y value to be even or odd.
        // We only calculate the second y root if it's needed.
        return (($prefix == PublicKey::KEY_COMPRESSED_EVEN) == $math->isEven($root0))
            ? $root0
            : $math->sub($prime, $root0);
    }
}
