<?php

namespace BitWasp\Bitcoin\Crypto\Random;

interface RbgInterface
{
    /**
     * Return $numBytes bytes deterministically derived from a seed
     *
     * @param int $numNumBytes
     * @return \BitWasp\Buffertools\Buffer
     */
    public function bytes($numNumBytes);
}
