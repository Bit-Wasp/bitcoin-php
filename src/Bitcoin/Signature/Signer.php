<?php

namespace Afk11\Bitcoin\Signature;

use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Crypto\Random\RbgInterface;
use Afk11\Bitcoin\Key\PrivateKeyInterface;
use Afk11\Bitcoin\Key\PublicKeyInterface;
use Mdanter\Ecc\GeneratorPoint;
use Mdanter\Ecc\MathAdapterInterface;

class Signer implements SignerInterface
{
    /**
     * @var bool
     */
    protected $lowSignatures;

    /**
     * @var GeneratorPoint
     */
    protected $generator;

    /**
     * @var MathAdapterInterface
     */
    protected $math;

    /**
     * @param MathAdapterInterface $math
     * @param GeneratorPoint $G
     * @param bool $forceLowSignatures
     */
    public function __construct(MathAdapterInterface $math, GeneratorPoint $G, $forceLowSignatures = true)
    {
        $this->math = $math;
        $this->generator = $G;
        $this->lowSignatures = $forceLowSignatures;
    }

    /**
     * Produce a signature for a $messageHash by a $privateKey. $kProvider can be random or
     * deterministic (Rfc6979)
     *
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $messageHash
     * @param RbgInterface $nonce
     * @return Signature
     */
    public function sign(PrivateKeyInterface $privateKey, Buffer $messageHash, RbgInterface $nonce)
    {
        $randomK = $nonce->bytes(32);

        $n       = $this->generator->getOrder();
        $k       = $this->math->mod($randomK->serialize('int'), $n);
        $r       = $this->generator->mul($k)->getX();

        if ($this->math->cmp($r, 0) == 0) {
            throw new \RuntimeException('Random number r = 0');
        }

        $s  = $this->math->mod(
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
        if ($this->lowSignatures && $this->math->cmp($s, $this->math->div($this->math->add($n, 1), 2))) {
            $s = $this->math->sub($n, $s);
        }

        return new Signature($r, $s);
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @param \Afk11\Bitcoin\Buffer $hash
     * @param SignatureInterface $signature
     * @return bool
     */
    public function verify(PublicKeyInterface $publicKey, Buffer $hash, SignatureInterface $signature)
    {
        $n     = $this->generator->getOrder();
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
        $u1 = $this->math->mod($this->math->mul($hash->serialize('int'), $c), $n);
        $u2 = $this->math->mod($this->math->mul($r, $c), $n);
        $xy = $this->generator->mul($u1)->add($point->mul($u2));
        $v = $this->math->mod($xy->getX(), $n);

        return $this->math->cmp($v, $r) == 0;
    }
}
