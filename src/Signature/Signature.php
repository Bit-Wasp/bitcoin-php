<?php

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Signature\DerSignatureSerializer;

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
        $serializer = new DerSignatureSerializer(Bitcoin::getMath());
        $buffer = $serializer->serialize($this);
        return $buffer;
    }
}
