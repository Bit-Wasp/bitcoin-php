<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\SerializableInterface;

interface BlockHeaderInterface extends SerializableInterface
{
    const CURRENT_VERSION = 2;

    /**
     * @return int
     */
    public function getVersion();

    /**
     * @param $version
     * @return BlockHeaderInterface
     */
    public function setVersion($version);

    /**
     * @return string
     */
    public function getPrevBlock();

    /**
     * @param $prevBlock
     * @return BlockHeaderInterface
     */
    public function setPrevBlock($prevBlock);
    /**
     * @return string
     */
    public function getNextBlock();

    /**
     * @param $nextBlock
     * @return BlockHeaderInterface
     */
    public function setNextBlock($nextBlock);

    /**
     * @return string
     */
    public function getMerkleRoot();

    /**
     * @param $merkleRoot
     * @return BlockHeaderInterface
     */
    public function setMerkleRoot($merkleRoot);

    /**
     * @return string
     */
    public function getTimestamp();

    /**
     * @param $timestamp
     * @return BlockHeaderInterface
     */
    public function setTimestamp($timestamp);

    /**
     * @return Buffer|null
     */
    public function getBits();

    /**
     * @param Buffer $bits
     * @return BlockHeaderInterface
     */
    public function setBits(Buffer $bits);

    /**
     * @return string
     */
    public function getNonce();

    /**
     * @param $nonce
     * @return BlockHeaderInterface
     */
    public function setNonce($nonce);

    /**
     * @return string
     */
    public function getBlockHash();
}
