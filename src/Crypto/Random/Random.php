<?php

namespace BitWasp\Bitcoin\Crypto\Random;

use BitWasp\Bitcoin\Exceptions\RandomBytesFailure;
use BitWasp\Buffertools\Buffer;

class Random implements RbgInterface
{
    /**
     * Return $length bytes. Throws an exception if
     * @param int $length
     * @return Buffer
     * @throws RandomBytesFailure
     */
    public function bytes($length = 32)
    {
        return new Buffer(random_bytes($length), $length);
    }
}
