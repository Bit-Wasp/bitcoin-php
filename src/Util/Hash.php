<?php

namespace Bitcoin\Util;

/**
 * Class Hash
 * @package Bitcoin
 */
class Hash
{
    /**
     * Normalize data so it is always a
     * @param $input
     * @return string
     */
    private static function normalize($input)
    {
        if ($input instanceof Buffer) {
            $input = $input->serialize('hex');
        }

        return $input;
    }

    /**
     * Calculate Sha256(RipeMd160()) on the given data
     *
     * @param $data
     * @param bool $binary_output
     * @return string
     */
    public static function sha256ripe160($data, $binary_output = false)
    {
        $data = self::normalize($data);
        $data = pack("H*", $data);
        $hash = self::sha256($data, true);
        $hash = self::ripemd160($hash, $binary_output);
        return $hash;
    }

    /**
     * Perform SHA256
     *
     * @param $data
     * @param bool $binary_output
     * @return string
     */
    public static function sha256($data, $binary_output = false)
    {
        $hash = hash('sha256', $data, $binary_output);
        return $hash;
    }

    /**
     * Perform SHA256 twice
     *
     * @param $data
     * @param bool $binary_output
     * @return string
     */
    public static function sha256d($data, $binary_output = false)
    {
        $data = self::normalize($data);
        $hash = self::sha256($data, true);
        $hash = self::sha256($hash, $binary_output);
        return $hash;
    }

    /**
     * RIPEMD160
     *
     * @param $data
     * @param bool $binary_output
     * @return string
     */
    public static function ripemd160($data, $binary_output = false)
    {
        $hash = hash('ripemd160', $data, $binary_output);
        return $hash;
    }

    /**
     * RIPEMD160 twice
     *
     * @param $data
     * @param bool $binary_output
     * @return string
     */
    public static function ripemd160d($data, $binary_output = false)
    {
        $data = self::normalize($data);
        $bs = pack("H*", $data);
        $hash = self::ripemd160($data, true);
        $hash = self::ripemd160($hash, $binary_output);
        return $hash;
    }

    /**
     * @param $data
     * @param bool $binary_output
     * @return string
     */
    public static function sha1($data, $binary_output = false)
    {
        $data = self::normalize($data);
        $hash = hash('sha1', $data, $binary_output);
        return $hash;
    }

    /**
     * PBKDF2
     *
     * @param $algo
     * @param $data
     * @param $salt
     * @param $iterations
     * @param $length
     * @param bool $binary_output
     * @return mixed
     */
    public static function pbkdf2($algo, $data, $salt, $iterations, $length, $binary_output = false)
    {
        $data = self::normalize($data);
        return hash_pbkdf2($algo, $data, $salt, $iterations, $length, $binary_output);
    }

    /**
     * @param $algo
     * @param $data
     * @param $salt
     * @return string
     */
    public static function hmac($algo, $data, $salt)
    {
        $data = self::normalize($data);
        return hash_hmac($algo, $data, $salt);
    }
}
