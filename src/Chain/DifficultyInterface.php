<?php

namespace Afk11\Bitcoin\Chain;

use Afk11\Bitcoin\Buffer;

interface DifficultyInterface
{
    public function lowestBits();
    public function getTarget(Buffer $bits);
    public function getTargetHash(Buffer $bits);
    public function getMaxTarget();
    public function getDifficulty(Buffer $bits);
}
