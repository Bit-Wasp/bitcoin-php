<?php

namespace Afk11\Bitcoin\Crypto\Random;

use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Exceptions\RandomBytesFailure;

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
        $random = mcrypt_create_iv($length, \MCRYPT_DEV_URANDOM);

        if (!$random) {
            throw new RandomBytesFailure('Failed to generate random bytes');
        }

        return new Buffer($random);
    }
}
