<?php

namespace Afk11\Bitcoin\Crypto\Random;

interface RbgInterface
{
    /**
     * Return $numBytes bytes deterministically derived from a seed
     *
     * @param int $numNumBytes
     * @return \Afk11\Bitcoin\Buffer
     */
    public function bytes($numNumBytes);
}
