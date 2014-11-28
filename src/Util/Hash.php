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
        $hash = self::ripemd160($data, true);
        $hash = self::ripemd160($hash, $binary_output);
        return $hash;
    }

    /**
     * Calculate a SHA1 hash
     *
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
     * PBKDF2 - with support for older PHP versions
     *
     * @param $algorithm
     * @param $password
     * @param $salt
     * @param $count
     * @param $key_length
     * @param bool $raw_output
     * @return string
     */
    public static function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
    {
        $password   = self::normalize($password);
        $key_length = $key_length / 2;
        $algorithm  = strtolower($algorithm);

        if (!in_array($algorithm, hash_algos(), true)) {
            trigger_error('PBKDF2 ERROR: Invalid hash algorithm.', E_USER_ERROR);
        }
        if ($count <= 0 || $key_length <= 0) {
            trigger_error('PBKDF2 ERROR: Invalid parameters.', E_USER_ERROR);
        }

        if (function_exists("hash_pbkdf2")) {
            // The output length is in NIBBLES (4-bits) if $raw_output is false!
            if (!$raw_output) {
                $key_length = $key_length * 2;
            }
            return hash_pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output);
        }

        $hash_length = strlen(hash($algorithm, "", true));
        $block_count = ceil($key_length / $hash_length);

        $output = "";
        for ($i = 1; $i <= $block_count; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last = $salt . pack("N", $i);
            // first iteration
            $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < $count; $j++) {
                $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
            }
            $output .= $xorsum;
        }

        if ($raw_output) {
            return substr($output, 0, $key_length);
        } else {
            return bin2hex(substr($output, 0, $key_length));
        }
    }

    /**
     * Do HMAC hashing on $data and $salt
     *
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
