<?php

namespace Bitcoin\Crypto;

use \Bitcoin\Util\Buffer;
use \Bitcoin\Exceptions\RandomBytesFailure;

/**
 * Class Random
 * @package Bitcoin\Random
 */
class Random
{

    /**
     * Return $length bytes. Throws an exception if
     * @param int $length
     * @return Buffer
     * @throws RandomBytesFailure
     */
    public static function bytes($length = 32)
    {
        $random = mcrypt_create_iv(32, \MCRYPT_DEV_URANDOM);

        if (!$random) {
            throw new RandomBytesFailure('Failed to generate random bytes');
        }

        return new Buffer($random);
    }
}
