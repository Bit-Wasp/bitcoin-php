<?php
/**
 * Created by PhpStorm.
 * User: tk
 * Date: 2/25/18
 * Time: 3:32 PM
 */

namespace BitWasp\Bitcoin\Serializer\Key\HierarchicalKey;

use BitWasp\Buffertools\BufferInterface;

class RawKeyParams
{
    private $prefix;
    private $depth;
    private $fingerprint;
    private $sequence;
    private $chainCode;
    private $keyData;

    public function __construct($prefix, $depth, $fingerprint, $sequence, BufferInterface $chainCode, BufferInterface $keyData)
    {
        $this->prefix = $prefix;
        $this->depth = $depth;
        $this->fingerprint = $fingerprint;
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
    public function getFingerprint()
    {
        return $this->fingerprint;
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
