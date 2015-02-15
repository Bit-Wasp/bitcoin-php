<?php

namespace Afk11\Bitcoin\Block;

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
