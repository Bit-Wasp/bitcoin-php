<?php

namespace Bitcoin\Block;

/**
 * Interface BlockHeaderInterface
 * @package Bitcoin\Block
 * @author Thomas Kerin
 */
interface BlockHeaderInterface
{
    public function getVersion();
    public function getPrevBlock();
    public function getNextBlock();
    public function getMerkleRoot();
    public function getTimestamp();
    public function getBits();
    public function getNonce();
}
