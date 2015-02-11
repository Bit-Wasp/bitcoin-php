<?php

namespace Bitcoin\Crypto\Random;

use Bitcoin\Buffer;
use Bitcoin\Crypto\Random\RBGInterface;
use \Bitcoin\Exceptions\RandomBytesFailure;

/**
 * Class Random
 * @package Bitcoin\Random
 */
class Random implements RBGInterface
{

    /**
     * Return $length bytes. Throws an exception if
     * @param int $length
     * @return Buffer
     * @throws RandomBytesFailure
     */
    public function bytes($length = 32)
    {
        $random = mcrypt_create_iv(32, \MCRYPT_DEV_URANDOM);

        if (!$random) {
            throw new RandomBytesFailure('Failed to generate random bytes');
        }

        return new Buffer($random);
    }
}
