<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Signature\DerSignatureSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Buffertools\BufferInterface;

class Signature extends Serializable implements SignatureInterface, \Mdanter\Ecc\Crypto\Signature\SignatureInterface
{
    /**
     * @var \GMP
     */
    private $r;

    /**
     * @var \GMP
     */
    private $s;

    /**
     * @var EcAdapter
     */
    private $ecAdapter;

    /**
     * @param EcAdapter $ecAdapter
     * @param \GMP $r
     * @param \GMP $s
     */
    public function __construct(EcAdapter $ecAdapter, \GMP $r, \GMP $s)
    {
        $this->ecAdapter = $ecAdapter;
        $this->r = $r;
        $this->s = $s;
    }

    /**
     * @inheritdoc
     * @see SignatureInterface::getR()
     */
    public function getR(): \GMP
    {
        return $this->r;
    }

    /**
     * @inheritdoc
     * @see SignatureInterface::getS()
     */
    public function getS(): \GMP
    {
        return $this->s;
    }

    /**
     * @param Signature $signature
     * @return bool
     */
    public function doEquals(Signature $signature): bool
    {
        $math = $this->ecAdapter->getMath();
        return $math->equals($this->getR(), $signature->getR())
            && $math->equals($this->getS(), $signature->getS());
    }

    /**
     * @param SignatureInterface $signature
     * @return bool
     */
    public function equals(SignatureInterface $signature): bool
    {
        /** @var Signature $signature */
        return $this->doEquals($signature);
    }

    /**
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function getBuffer(): BufferInterface
    {
        return (new DerSignatureSerializer($this->ecAdapter))->serialize($this);
    }
}
