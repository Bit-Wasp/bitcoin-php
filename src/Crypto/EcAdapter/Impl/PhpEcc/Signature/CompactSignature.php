<?php


namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Signature\CompactSignatureSerializer;

class CompactSignature extends Signature implements CompactSignatureInterface
{
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
        if (!is_bool($compressed)) {
            throw new \InvalidArgumentException('CompactSignature: $compressed must be a boolean');
        }

        $this->recid = $recid;
        $this->compressed = $compressed;
        parent::__construct($r, $s);
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
     * @return \BitWasp\Buffertools\Buffer
     */
    public function getBuffer()
    {
        return (new CompactSignatureSerializer(Bitcoin::getMath()))->serialize($this);
    }
}
