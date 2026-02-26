<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SchnorrSignatureInterface;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;

class SchnorrSignature extends Serializable implements SchnorrSignatureInterface
{
    private $r;
    private $s;

    public function __construct(\GMP $r, \GMP $s)
    {
        $this->r = $r;
        $this->s = $s;
    }

    public function getR(): \GMP
    {
        return $this->r;
    }

    public function getS(): \GMP
    {
        return $this->s;
    }

    public function getBuffer(): BufferInterface
    {
        return Buffertools::concat(Buffer::int(gmp_strval($this->r), 32), Buffer::int(gmp_strval($this->s), 32));
    }
}
