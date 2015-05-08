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
     * @return string
     */
    public function getPrevBlock();

    /**
     * @return string
     * @throws \RuntimeException
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
     * @return string
     */
    public function getTimestamp();

    /**
     * @return Buffer|null
     */
    public function getBits();

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
