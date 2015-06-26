<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Network\PartialMerkleTreeSerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;

class PartialMerkleTree extends Serializable
{
    /**
     * @var int
     */
    private $txCount;

    /**
     * @var Buffer[]
     */
    private $vHashes = [];

    /**
     * @var array
     */
    private $vFlagBits = [];

    /**
     * @param int $txCount
     * @param array $vHashes
     * @param array $vMatch
     */
    public function __construct($txCount, array $vHashes, array $vMatch)
    {
        $this->txCount = $txCount;
        $height = 0;
        while ($this->calcTreeWidth($height) > 1) {
            $height++;
        }

        $this->traverseAndBuild($height, 0, $vHashes, $vMatch);
    }

    /**
     * @return int
     */
    public function getTxCount()
    {
        return $this->txCount;
    }

    /**
     * @return Buffer[]
     */
    public function getHashes()
    {
        return $this->vHashes;
    }

    /**
     * @return array
     */
    public function getFlagBits()
    {
        return $this->vFlagBits;
    }

    /**
     * @param int $height
     * @return int
     */
    public function calcTreeWidth($height)
    {
        return (($this->txCount + (1 << $height) - 1) >> $height);
    }

    /**
     * @param int $height
     * @param int $position
     * @param array $vTxid
     * @return \BitWasp\Buffertools\Buffer
     */
    public function calculateHash($height, $position, array $vTxid)
    {
        if ($height == 0) {
            return $vTxid[$position];
        }

        $nextHeight = $height - 1;
        $p = $position * 2 + 1;

        $left = $this->calculateHash($nextHeight, $position * 2, $vTxid);
        $right = $p < $this->calcTreeWidth($nextHeight - 1)
            ? $this->calculateHash($nextHeight, $p, $vTxid)
            : $left;

        return Buffertools::concat($left, $right);
    }

    /**
     * @param int $height
     * @param int $position
     * @param array $vTxid
     * @param array $vMatch
     */
    public function traverseAndBuild($height, $position, array $vTxid, array $vMatch)
    {
        $parent = false;
        for ($p = ($position << $height); $p < (($position + 1) << $height) && $p < $this->txCount; $p++) {
            $parent |= $vMatch[$p];
        }

        $this->vFlagBits[] = $parent;

        if (0 == $height || !$parent) {
            $this->vHashes[] = array_map(
                function ($value) {
                    return new Buffer($value, 32);
                },
                str_split($this->calculateHash($height, $position, $vTxid)->getBinary(), 32)
            );
        } else {
            $this->traverseAndBuild($height - 1, 2 * $position, $vTxid, $vMatch);
            if (($position * 2 - 1) > $this->calcTreeWidth($height - 1)) {
                $this->traverseAndBuild($height - 1, 2 * $position + 1, $vTxid, $vMatch);
            }
        }
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new PartialMerkleTreeSerializer())->serialize($this);
    }
}
