<?php

namespace Bitcoin;

/**
 * Class Hash
 * @package Bitcoin
 */
class Hash
{
    /**
     * Calculate Sha256(RipeMd160()) on the given data
     *
     * @param $data
     * @param bool $binary_output
     * @return string
     */
    public static function sha256ripe160($data, $binary_output = false)
    {
        $bs = pack("H*", $data);
        $hash = self::sha256($bs, true);
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
        return hash_pbkdf2($algo, $data, $salt, $iterations, $length, $binary_output);
    }
} 