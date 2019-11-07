<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PrivateKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\XOnlyPublicKey;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Buffertools\Buffer;
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
     * @return SchnorrSignature
     * @throws \Exception
     */
    public function sign(PrivateKey $privateKey, BufferInterface $message32): SchnorrSignature
    {
        $G = $this->adapter->getGenerator();
        $n = $G->getOrder();
        $d = $privateKey->getSecret();
        $P = $privateKey->getXOnlyPublicKey();
        if (!$P->hasSquareY()) {
            $d = gmp_sub($n, $d);
        }
        $k = $this->hashPrivateData($d, $message32, $n);
        if (gmp_cmp($k, 0) === 0) {
            throw new \RuntimeException("unable to produce signature");
        }
        $R = $G->mul($k);
        if (gmp_jacobi($R->getY(), $G->getCurve()->getPrime()) !== 1) {
            $k = gmp_sub($n, $k);
        }

        $e = $this->hashPublicData($R->getX(), $privateKey->getXOnlyPublicKey(), $message32, $n);
        $s = gmp_mod(gmp_add($k, gmp_mod(gmp_mul($e, $d), $n)), $n);
        return new SchnorrSignature($R->getX(), $s);
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
     * @param \GMP $secret
     * @param BufferInterface $message32
     * @param \GMP $n
     * @return \GMP
     * @throws \Exception
     */
    private function hashPrivateData(\GMP $secret, BufferInterface $message32, \GMP $n): \GMP
    {
        $hash = Hash::taggedSha256("BIPSchnorrDerive", new Buffer($this->tob32($secret) . $message32->getBinary()));
        return gmp_mod($hash->getGmp(), $n);
    }

    /**
     * @param \GMP $Rx
     * @param XOnlyPublicKey $publicKey
     * @param BufferInterface $message32
     * @param \GMP $n
     * @param string|null $rxBytes
     * @return \GMP
     * @throws \Exception
     */
    private function hashPublicData(\GMP $Rx, XOnlyPublicKey $publicKey, BufferInterface $message32, \GMP $n, string &$rxBytes = null): \GMP
    {
        $rxBytes = $this->tob32($Rx);
        $hash = Hash::taggedSha256("BIPSchnorr", new Buffer($rxBytes . $publicKey->getBinary() . $message32->getBinary()));
        return gmp_mod(gmp_init($hash->getHex(), 16), $n);
    }

    public function verify(BufferInterface $msg32, XOnlyPublicKey $publicKey, SchnorrSignature $signature): bool
    {
        $G = $this->adapter->getGenerator();
        $n = $G->getOrder();
        $p = $G->getCurve()->getPrime();

        $r = $signature->getR();
        $s = $signature->getS();
        if (gmp_cmp($r, $p) >= 0 || gmp_cmp($s, $n) >= 0) {
            return false;
        }

        if (gmp_jacobi($publicKey->getPoint()->getY(), $p) !== 1) {
            throw new \RuntimeException("public key wrong has_square_y");
        }

        $RxBytes = null;
        $e = $this->hashPublicData($r, $publicKey, $msg32, $n, $RxBytes);
        $R = $G->mul($s)->add($publicKey->getPoint()->mul(gmp_sub($n, $e)));
        $jacobiNotOne = gmp_jacobi($R->getY(), $p) !== 1;
        $rxNotEquals = !hash_equals($RxBytes, $this->tob32($R->getX()));
        if ($jacobiNotOne || $rxNotEquals) {
            return false;
        }
        return true;
    }
}
