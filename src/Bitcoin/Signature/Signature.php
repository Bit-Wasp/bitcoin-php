<?php

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Exceptions\SignatureNotCanonical;
use BitWasp\Bitcoin\Serializable;

class Signature extends Serializable implements SignatureInterface
{
    /**
     * @var int
     */
    protected $r;

    /**
     * @var int
     */
    protected $s;

    /**
     * @var int
     */
    protected $sighashType;

    /**
     * @param $r
     * @param $s
     * @param int $sighashType
     */
    public function __construct($r, $s, $sighashType = SignatureHashInterface::SIGHASH_ALL)
    {
        $this
            ->setR($r)
            ->setS($s)

            ->setSighashType($sighashType);
    }

    /**
     * @inheritdoc
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * @param $r
     * @return $this
     */
    private function setR($r)
    {
        $this->r = $r;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getS()
    {
        return $this->s;
    }

    /**
     * @param $s
     * @return $this
     */
    private function setS($s)
    {
        $this->s = $s;
        return $this;
    }

    /**
     * Return the SIGHASH type for this signature
     *
     * @return int
     */
    public function getSighashType()
    {
        return $this->sighashType;
    }

    /**
     * @param integer $hashtype
     * @return $this
     */
    private function setSighashType($hashtype)
    {
        $this->sighashType = $hashtype;
        return $this;
    }

    /**
     * @return \BitWasp\Buffertools\Buffer
     */
    public function getBuffer()
    {
        $serializer = SignatureFactory::getSerializer();
        $buffer = $serializer->serialize($this);
        return $buffer;
    }
}
