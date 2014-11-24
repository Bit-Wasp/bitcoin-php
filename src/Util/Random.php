<?php

namespace Bitcoin\Util;

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
     * @param int $length
     * @return string
     * @throws \Exception
     */
    public static function bytes($length = 32)
    {
        if (!self::hasOpenssl()) {
            throw new \Exception('Openssl not found');
        }

        $strong = true;
        $random = openssl_random_pseudo_bytes($length, $strong);

        if (!$strong) {
            throw new \Exception('Insufficient entropy for cryptographic operations');
        }

        return $random;
    }
}
