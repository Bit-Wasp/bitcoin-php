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
     * @return int|string
     */
    public function getNonce();

    /**
     * @return Buffer
     */
    public function getHash();
}
