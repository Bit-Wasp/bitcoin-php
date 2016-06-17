<?php


namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Signature\CompactSignatureSerializer;

class CompactSignature extends Signature implements CompactSignatureInterface
{
    /**
     * @var EcAdapter
     */
    private $ecAdapter;

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
     * @param \GMP $r
     * @param \GMP $s
     * @param int $recid
     * @param bool $compressed
     */
    public function __construct(EcAdapter $adapter, \GMP $r, \GMP $s, $recid, $compressed)
    {
        if (!is_bool($compressed)) {
            throw new \InvalidArgumentException('CompactSignature: $compressed must be a boolean');
        }

        $this->ecAdapter = $adapter;
        $this->recid = $recid;
        $this->compressed = $compressed;
        parent::__construct($adapter, $r, $s);
    }

    /**
     * @return Signature
     */
    public function convert()
    {
        return new Signature($this->ecAdapter, $this->getR(), $this->getS());
    }

    /**
     * @return int
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
     * @return int
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
