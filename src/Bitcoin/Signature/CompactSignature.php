<?php


namespace BitWasp\Bitcoin\Signature;

class CompactSignature
{
    /**
     * @var int|string
     */
    protected $r;

    /**
     * @var int|string
     */
    protected $s;

    /**
     * @var int|string
     */
    protected $recid;

    /**
     * @var bool
     */
    protected $compressed;

    /**
     * @param $r
     * @param $s
     * @param $recid
     */
    public function __construct($r, $s, $recid, $compressed)
    {
        $this->r = $r;
        $this->s = $s;
        $this->recid = $recid;
        $this->compressed = $compressed;
    }

    public function getR()
    {
        return $this->r;
    }

    public function getS()
    {
        return $this->s;
    }

    public function getRecoveryId()
    {
        return $this->recid;
    }

    public function isCompressed()
    {
        return $this->compressed;
    }

    public function getFlags()
    {
        return $this->getRecoveryId() + 27 + ($this->isCompressed() ? 4 : 0);
    }

    public function getBuffer()
    {

    }
}
