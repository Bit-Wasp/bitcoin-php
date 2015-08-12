<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Signature\DerSignatureSerializer;

class Signature extends Serializable implements SignatureInterface
{
    /**
     * @var int
     */
    private $r;

    /**
     * @var int
     */
    private $s;

    /**
     * @param $r
     * @param $s
     */
    public function __construct($r, $s)
    {
        $this->r = $r;
        $this->s = $s;
    }

    /**
     * @inheritdoc
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * @inheritdoc
     */
    public function getS()
    {
        return $this->s;
    }

    /**
     * @return \BitWasp\Buffertools\Buffer
     */
    public function getBuffer()
    {
        return (new DerSignatureSerializer(Bitcoin::getMath()))->serialize($this);
    }
}
