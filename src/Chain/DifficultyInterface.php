<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/01/15
 * Time: 03:36
 */

namespace Afk11\Bitcoin\Chain;

use Bitcoin\Buffer;

interface DifficultyInterface
{
    public function lowestBits();
    public function getTarget(Buffer $bits);
    public function getTargetHash(Buffer $bits);
    public function getMaxTarget();
    public function getDifficulty(Buffer $bits);
}
