<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Signature\CompactSignatureSerializer;
use BitWasp\Buffertools\BufferInterface;

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
    public function __construct(EcAdapter $adapter, \GMP $r, \GMP $s, int $recid, bool $compressed)
    {
        $this->ecAdapter = $adapter;
        $this->recid = $recid;
        $this->compressed = $compressed;
        parent::__construct($adapter, $r, $s);
    }

    /**
     * @return Signature
     */
    public function convert(): Signature
    {
        return new Signature($this->ecAdapter, $this->getR(), $this->getS());
    }

    /**
     * @return int
     */
    public function getRecoveryId(): int
    {
        return $this->recid;
    }

    /**
     * @return bool
     */
    public function isCompressed(): bool
    {
        return $this->compressed;
    }

    /**
     * @return int
     */
    public function getFlags(): int
    {
        return $this->getRecoveryId() + 27 + ($this->isCompressed() ? 4 : 0);
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer(): BufferInterface
    {
        return (new CompactSignatureSerializer($this->ecAdapter))->serialize($this);
    }
}
