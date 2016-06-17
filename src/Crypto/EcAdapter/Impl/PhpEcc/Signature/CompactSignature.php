<?php


namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Signature\CompactSignatureSerializer;

class CompactSignature extends Serializable implements CompactSignatureInterface
{
    /**
     * @var
     */
    private $ecAdapter;

    /**
     * @var int
     */
    private $r;

    /**
     * @var int
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
     * @param EcAdapter $adapter
     * @param int $r
     * @param int $s
     * @param int $recid
     * @param bool $compressed
     */
    public function __construct(EcAdapter $adapter, $r, $s, $recid, $compressed)
    {
        if (!is_bool($compressed)) {
            throw new \InvalidArgumentException('CompactSignature: $compressed must be a boolean');
        }

        $this->ecAdapter = $adapter;
        $this->recid = $recid;
        $this->compressed = $compressed;
        $this->r = $r;
        $this->s = $s;
    }

    /**
     * @return int
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * @return int
     */
    public function getS()
    {
        return $this->s;
    }

    /**
     * @return Signature
     */
    public function convert()
    {
        return new Signature($this->ecAdapter, $this->r, $this->s);
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
        return $this->compressed;
    }

    /**
     * @return int|string
     */
    public function getFlags()
    {
        return $this->getRecoveryId() + 27 + ($this->isCompressed() ? 4 : 0);
    }

    /**
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function getBuffer()
    {
        return (new CompactSignatureSerializer($this->ecAdapter))->serialize($this);
    }
}
