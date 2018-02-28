<?php

namespace BitWasp\Bitcoin\Serializer\Key\HierarchicalKey;

use BitWasp\Buffertools\BufferInterface;

class RawKeyParams
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var int
     */
    private $depth;

    /**
     * @var int
     */
    private $parentFpr;

    /**
     * @var int
     */
    private $sequence;

    /**
     * @var BufferInterface
     */
    private $chainCode;

    /**
     * @var BufferInterface
     */
    private $keyData;

    /**
     * RawKeyParams constructor.
     * @param string $prefix
     * @param int $depth
     * @param int $parentFingerprint
     * @param int $sequence
     * @param BufferInterface $chainCode
     * @param BufferInterface $keyData
     */
    public function __construct($prefix, $depth, $parentFingerprint, $sequence, BufferInterface $chainCode, BufferInterface $keyData)
    {
        $this->prefix = $prefix;
        $this->depth = $depth;
        $this->parentFpr = $parentFingerprint;
        $this->sequence = $sequence;
        $this->chainCode = $chainCode;
        $this->keyData = $keyData;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * @return int
     */
    public function getParentFingerprint()
    {
        return $this->parentFpr;
    }

    /**
     * @return int
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * @return BufferInterface
     */
    public function getChainCode()
    {
        return $this->chainCode;
    }

    /**
     * @return BufferInterface
     */
    public function getKeyData()
    {
        return $this->keyData;
    }
}
