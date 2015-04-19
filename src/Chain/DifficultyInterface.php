<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Buffertools\Buffer;

interface DifficultyInterface
{
    public function lowestBits();
    public function getTarget(Buffer $bits);
    public function getTargetHash(Buffer $bits);
    public function getMaxTarget();
    public function getDifficulty(Buffer $bits);
}
