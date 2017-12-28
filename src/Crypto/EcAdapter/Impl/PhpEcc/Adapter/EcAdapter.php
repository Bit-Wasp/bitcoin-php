<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PrivateKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature\CompactSignature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\CompactSignatureInterface;
use BitWasp\Bitcoin\Math\Math;
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
    public function getMath(): Math
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
     * @return \GMP
     */
    public function getOrder(): \GMP
    {
        return $this->generator->getOrder();
    }

    /**
     * @param \GMP $scalar
     * @param bool|false $compressed
     * @return PrivateKeyInterface
     */
    public function getPrivateKey(\GMP $scalar, bool $compressed = false): PrivateKeyInterface
    {
        return new PrivateKey($this, $scalar, $compressed);
    }

    /**
     * @param BufferInterface $messageHash
     * @param CompactSignature|CompactSignatureInterface $signature
     * @return PublicKeyInterface
     * @throws \Exception
     */
    public function recover(BufferInterface $messageHash, CompactSignatureInterface $signature): PublicKeyInterface
    {
        $math = $this->getMath();
        $G = $this->generator;

        $one = gmp_init(1);

        $r = $signature->getR();
        $s = $signature->getS();
        $isYEven = ($signature->getRecoveryId() & 1) !== 0;
        $isSecondKey = ($signature->getRecoveryId() & 2) !== 0;
        $curve = $G->getCurve();

        // Precalculate (p + 1) / 4 where p is the field order
        $pOverFour = $math->div($math->add($curve->getPrime(), $one), gmp_init(4));

        // 1.1 Compute x
        if (!$isSecondKey) {
            $x = $r;
        } else {
            $x = $math->add($r, $G->getOrder());
        }

        // 1.3 Convert x to point
        $alpha = $math->mod($math->add($math->add($math->pow($x, 3), $math->mul($curve->getA(), $x)), $curve->getB()), $curve->getPrime());
        $beta = $math->powmod($alpha, $pOverFour, $curve->getPrime());

        // If beta is even, but y isn't or vice versa, then convert it,
        // otherwise we're done and y=beta.
        if ($math->isEven($beta) === $isYEven) {
            $y = $math->sub($curve->getPrime(), $beta);
        } else {
            $y = $beta;
        }

        // 1.4 Check that nR is at infinity (implicitly done in constructor)
        $R = $G->getCurve()->getPoint($x, $y);

        $pointNegate = function (PointInterface $p) use ($math, $G) {
            return $G->getCurve()->getPoint($p->getX(), $math->mul($p->getY(), gmp_init('-1', 10)));
        };

        // 1.6.1 Compute a candidate public key Q = r^-1 (sR - eG)
        $rInv = $math->inverseMod($r, $G->getOrder());
        $eGNeg = $pointNegate($G->mul($messageHash->getGmp()));
        $Q = $R->mul($s)->add($eGNeg)->mul($rInv);

        // 1.6.2 Test Q as a public key
        $Qk = new PublicKey($this, $Q, $signature->isCompressed());
        if ($Qk->verify($messageHash, $signature->convert())) {
            return $Qk;
        }

        throw new \Exception('Unable to recover public key');
    }

    /**
     * Attempt to calculate the public key recovery param by trial and error
     *
     * @param \GMP $r
     * @param \GMP $s
     * @param BufferInterface $messageHash
     * @param PublicKey $publicKey
     * @return int
     * @throws \Exception
     */
    public function calcPubKeyRecoveryParam(\GMP $r, \GMP $s, BufferInterface $messageHash, PublicKey $publicKey): int
    {
        $Q = $publicKey->getPoint();
        for ($i = 0; $i < 4; $i++) {
            try {
                $recover = $this->recover($messageHash, new CompactSignature($this, $r, $s, $i, $publicKey->isCompressed()));
                if ($Q->equals($recover->getPoint())) {
                    return $i;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new \Exception('Failed to find valid recovery factor');
    }

    /**
     * @param BufferInterface $privateKey
     * @return bool
     */
    public function validatePrivateKey(BufferInterface $privateKey): bool
    {
        $math = $this->math;
        $scalar = $privateKey->getGmp();
        return $math->cmp($scalar, gmp_init(0)) > 0 && $math->cmp($scalar, $this->getOrder()) < 0;
    }

    /**
     * @param \GMP $element
     * @param bool $half
     * @return bool
     */
    public function validateSignatureElement(\GMP $element, bool $half = false): bool
    {
        $math = $this->getMath();
        $against = $this->getOrder();
        if ($half) {
            $against = $math->rightShift($against, 1);
        }

        return $math->cmp($element, $against) < 0 && $math->cmp($element, gmp_init(0)) !== 0;
    }

    /**
     * @param BufferInterface $publicKey
     * @return PublicKeyInterface
     * @throws \Exception
     */
    public function publicKeyFromBuffer(BufferInterface $publicKey): PublicKeyInterface
    {
        $prefix = $publicKey->slice(0, 1)->getBinary();
        $size = $publicKey->getSize();
        $compressed = false;
        if ($prefix === PublicKey::KEY_UNCOMPRESSED || $prefix === "\x06" || $prefix === "\x07") {
            if ($size !== PublicKey::LENGTH_UNCOMPRESSED) {
                throw new \Exception('Invalid length for uncompressed key');
            }
        } else if ($prefix === PublicKey::KEY_COMPRESSED_EVEN || $prefix === PublicKey::KEY_COMPRESSED_ODD) {
            if ($size !== PublicKey::LENGTH_COMPRESSED) {
                throw new \Exception('Invalid length for compressed key');
            }
            $compressed = true;
        } else {
            throw new \Exception('Unknown public key prefix');
        }
        
        $x = $publicKey->slice(1, 32)->getGmp();
        $curve = $this->generator->getCurve();
        $y = $compressed
            ? $curve->recoverYfromX($prefix === PublicKey::KEY_COMPRESSED_ODD, $x)
            : $publicKey->slice(33, 32)->getGmp();

        return new PublicKey(
            $this,
            $curve->getPoint($x, $y),
            $compressed,
            $prefix
        );
    }
}
