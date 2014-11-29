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
     * @var bool
     */
    protected static $hasOpenssl;

    /**
     * Check if OpenSSL is loaded
     *
     * @return bool
     */
    private static function hasOpenssl()
    {
        if (self::$hasOpenssl == null) {
            self::$hasOpenssl = extension_loaded('openssl');
        }

        return self::$hasOpenssl;
    }

    /**
     * Return $length bytes. Throws an exception if
     * @param int $length
     * @return string
     * @throws \Exception
     */
    public static function bytes($length = 32)
    {
        if (!self::hasOpenssl()) {
            throw new InsufficientEntropy('Openssl not found');
        }

        $strong = true;
        $random = openssl_random_pseudo_bytes($length, $strong);

        if (!$strong) {
            throw new \Exception('Insufficient entropy for cryptographic operations');
        }

        return $random;
    }
}
