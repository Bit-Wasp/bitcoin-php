<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\Random;

use BitWasp\Buffertools\BufferInterface;

interface RbgInterface
{
    /**
     * Return $numBytes bytes deterministically derived from a seed
     *
     * @param int $numNumBytes
     * @return BufferInterface
     */
    public function bytes(int $numNumBytes): BufferInterface;
}
