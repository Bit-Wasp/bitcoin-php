<?php

namespace Bitcoin\Util;

use \Bitcoin\Exceptions\InsufficientEntropy;

/**
 * Class Random
 * @package Bitcoin\Random
 */
class Random
{

    /**
     * Return $length bytes. Throws an exception if
     * @param int $length
     * @return string
     * @throws \Exception
     */
    public static function bytes($length = 32)
    {
        $strong = true;
        $random = openssl_random_pseudo_bytes($length, $strong);

        if (!$strong) {
            throw new \Exception('Insufficient entropy for cryptographic operations');
        }

        return $random;
    }
}
