<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\SerializableInterface;

interface BlockHeaderInterface extends SerializableInterface
{
    const CURRENT_VERSION = 2;

    /**
     * Return the version of this block.
     *
     * @return int|string
     */
    public function getVersion();

    /**
     * Return the previous blocks hash.
     *
     * @return string
     */
    public function getPrevBlock();

    /**
     * Return the next block hash. Note this may not always be set.
     *
     * @return string
     */
    public function getNextBlock();

    /**
     * Set next block, to the provided $nextBlock hash.
     *
     * @param string $nextBlock
     * @return BlockHeaderInterface
     */
    public function setNextBlock($nextBlock);

    /**
     * Return the merkle root of the transactions in the block.
     *
     * @return string
     */
    public function getMerkleRoot();

    /**
     * Get the timestamp of the block.
     *
     * @return string
     */
    public function getTimestamp();

    /**
     * Return the buffer containing the short representation of the difficulty
     *
     * @return Buffer
     */
    public function getBits();

    /**
     * Return the nonce of the block header.
     *
     * @return string
     */
    public function getNonce();

    /**
     * Set the nonce of the block header. Used in mining.
     *
     * @param string $nonce
     * @return BlockHeaderInterface
     */
    public function setNonce($nonce);

    /**
     * Calculate the hash of this header.
     *
     * @return string
     */
    public function getBlockHash();
}
