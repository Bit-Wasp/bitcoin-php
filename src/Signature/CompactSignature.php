<?php


namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Signature\CompactSignatureSerializer;

class CompactSignature extends Serializable
{
    /**
     * @var int|string
     */
    private $r;

    /**
     * @var int|string
     */
    private $s;

    /**
     * @var int|string
     */
    private $recid;

    /**
     * @var bool
     */
    private $compressed;

    /**
     * @param int|string $r
     * @param int|string $s
     * @param int|string $recid
     * @param bool $compressed
     */
    public function __construct($r, $s, $recid, $compressed)
    {
        $this->r = $r;
        $this->s = $s;
        $this->recid = $recid;
        $this->compressed = $compressed;
    }

    /**
     * @return int|string
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * @return int|string
     */
    public function getS()
    {
        return $this->s;
    }

    /**
     * @return int|string
     */
    public function getRecoveryId()
    {
        return $this->recid;
    }

    /**
     * @return bool
     */
    public function isCompressed()
    {
        return $this->compressed === true;
    }

    /**
     * @return int|string
     */
    public function getFlags()
    {
        return $this->getRecoveryId() + 27 + ($this->isCompressed() ? 4 : 0);
    }

    /**
     * @return \BitWasp\Buffertools\Buffer
     */
    public function getBuffer()
    {
        $serializer = new CompactSignatureSerializer(Bitcoin::getMath());
        return $serializer->serialize($this);
    }
}
