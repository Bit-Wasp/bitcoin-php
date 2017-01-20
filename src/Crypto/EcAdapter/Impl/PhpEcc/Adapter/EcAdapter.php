<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PrivateKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature\CompactSignature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature\Signature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\CompactSignatureInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Bitcoin\Crypto\Random\RbgInterface;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Buffertools\BufferInterface;
use Mdanter\Ecc\Crypto\Signature\Signer;
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
     * @param \GMP $scalar
     * @param bool|false $compressed
     * @return PrivateKey
     */
    public function getPrivateKey(\GMP $scalar, $compressed = false)
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
     * @param \GMP $r
     * @param \GMP $s
     * @return Signature
     */
    public function getSignature(\GMP $r, \GMP $s)
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
        $hash = gmp_init($messageHash->getHex(), 16);
        $signer = new Signer($this->math);
        return $signer->verify($publicKey, $signature, $hash);
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
        $randomK = gmp_init($rbg->bytes(32)->getHex(), 16);
        $hash = gmp_init($messageHash->getHex(), 16);

        $signer = new Signer($this->math);
        $signature = $signer->sign($privateKey, $hash, $randomK);
        $s = $signature->getS();

        // if s is less than half the curve order, invert s
        if (!$this->validateSignatureElement($s, true)) {
            $s = $this->math->sub($this->generator->getOrder(), $s);
        }

        return new Signature($this, $signature->getR(), $s);
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

        $zero = gmp_init(0);
        $one = gmp_init(1);

        $r = $signature->getR();
        $s = $signature->getS();
        $recGMP = gmp_init($signature->getRecoveryId(), 10);
        $isYEven = $math->cmp($math->bitwiseAnd($recGMP, $one), $zero) !== 0;
        $isSecondKey = $math->cmp($math->bitwiseAnd($recGMP, gmp_init(2)), $zero) !== 0;
        $curve = $G->getCurve();

        // Precalculate (p + 1) / 4 where p is the field order
        $p_over_four = $math->div($math->add($curve->getPrime(), $one), gmp_init(4));

        // 1.1 Compute x
        if (!$isSecondKey) {
            $x = $r;
        } else {
            $x = $math->add($r, $G->getOrder());
        }

        // 1.3 Convert x to point
        $alpha = $math->mod($math->add($math->add($math->pow($x, 3), $math->mul($curve->getA(), $x)), $curve->getB()), $curve->getPrime());
        $beta = $math->powmod($alpha, $p_over_four, $curve->getPrime());

        // If beta is even, but y isn't or vice versa, then convert it,
        // otherwise we're done and y=beta.
        if ($math->isEven($beta) === $isYEven) {
            $y = $math->sub($curve->getPrime(), $beta);
        } else {
            $y = $beta;
        }

        // 1.4 Check that nR is at infinity (implicitly done in constructor)
        $R = $G->getCurve()->getPoint($x, $y);

        $point_negate = function (PointInterface $p) use ($math, $G) {
            return $G->getCurve()->getPoint($p->getX(), $math->mul($p->getY(), gmp_init('-1', 10)));
        };

        // 1.6.1 Compute a candidate public key Q = r^-1 (sR - eG)
        $rInv = $math->inverseMod($r, $G->getOrder());
        $eGNeg = $point_negate($G->mul($messageHash->getGmp()));
        $Q = $R->mul($s)->add($eGNeg)->mul($rInv);

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
     * @param \GMP $r
     * @param \GMP $s
     * @param BufferInterface $messageHash
     * @param PublicKey $publicKey
     * @return int
     * @throws \Exception
     */
    public function calcPubKeyRecoveryParam(\GMP $r, \GMP $s, BufferInterface $messageHash, PublicKey $publicKey)
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
        $scalar = $privateKey->getGmp();
        return $math->cmp($scalar, gmp_init(0)) > 0 && $math->cmp($scalar, $this->getGenerator()->getOrder()) < 0;
    }

    /**
     * @param \GMP $element
     * @param bool $half
     * @return bool
     */
    public function validateSignatureElement(\GMP $element, $half = false)
    {
        $math = $this->getMath();
        $against = $this->getGenerator()->getOrder();
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
    public function publicKeyFromBuffer(BufferInterface $publicKey)
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
