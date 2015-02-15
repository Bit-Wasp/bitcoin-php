<?php

namespace Afk11\Bitcoin\Block;

/**
 * Interface BlockHeaderInterface
 * @package Bitcoin\Block
 * @author Thomas Kerin
 */
interface BlockHeaderInterface
{
    const CURRENT_VERSION = 2;

    public function getVersion();
    public function getPrevBlock();
    public function getNextBlock();
    public function getMerkleRoot();
    public function getTimestamp();
    public function getBits();
    public function getNonce();
    public function getBlockHash();
}
