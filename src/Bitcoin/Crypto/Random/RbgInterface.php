<?php

namespace BitWasp\Bitcoin\Crypto\Random;

interface RbgInterface
{
    /**
     * Return $numBytes bytes deterministically derived from a seed
     *
     * @param int $numNumBytes
     * @return \BitWasp\Bitcoin\Buffer
     */
    public function bytes($numNumBytes);
}
