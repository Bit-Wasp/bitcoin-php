<?php

namespace Bitcoin\Crypto;

use Bitcoin\Util\Buffer;

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
    public static function normalize($input)
    {
        if ($input instanceof Buffer) {
            $input = $input->serialize();
        }

        return $input;
    }

    /**
     * Calculate Sha256(RipeMd160()) on the given data
     *
     * @param $data
     * @param bool $binaryOutput
     * @return string
     */
    public static function sha256ripe160($data, $binaryOutput = false)
    {
        $data = self::normalize($data);
        $data = pack("H*", $data);
        $hash = self::sha256($data, true);
        $hash = self::ripemd160($hash, $binaryOutput);
        return $hash;
    }

    /**
     * Perform SHA256
     *
     * @param $data
     * @param bool $binaryOutput
     * @return string
     */
    public static function sha256($data, $binaryOutput = false)
    {
        $hash = hash('sha256', $data, $binaryOutput);
        return $hash;
    }

    /**
     * Perform SHA256 twice
     *
     * @param $data
     * @param bool $binaryOutput
     * @return string
     */
    public static function sha256d($data, $binaryOutput = false)
    {
        $data = self::normalize($data);
        $hash = self::sha256($data, true);
        $hash = self::sha256($hash, $binaryOutput);
        return $hash;
    }

    /**
     * RIPEMD160
     *
     * @param $data
     * @param bool $binaryOutput
     * @return string
     */
    public static function ripemd160($data, $binaryOutput = false)
    {
        $hash = hash('ripemd160', $data, $binaryOutput);
        return $hash;
    }

    /**
     * RIPEMD160 twice
     *
     * @param $data
     * @param bool $binaryOutput
     * @return string
     */
    public static function ripemd160d($data, $binaryOutput = false)
    {
        $data = self::normalize($data);
        $hash = self::ripemd160($data, true);
        $hash = self::ripemd160($hash, $binaryOutput);
        return $hash;
    }

    /**
     * Calculate a SHA1 hash
     *
     * @param $data
     * @param bool $binaryOutput
     * @return string
     */
    public static function sha1($data, $binaryOutput = false)
    {
        $data = self::normalize($data);
        $hash = hash('sha1', $data, $binaryOutput);
        return $hash;
    }

    /**
     * PBKDF2
     *
     * @param $algorithm
     * @param $password
     * @param $salt
     * @param $count
     * @param $keyLength
     * @param bool $rawOutput
     * @return mixed
     * @throws \Exception
     */
    public static function pbkdf2($algorithm, $password, $salt, $count, $keyLength, $rawOutput = false)
    {
        $password   = self::normalize($password);
        $algorithm  = strtolower($algorithm);

        if (!in_array($algorithm, hash_algos(), true)) {
            throw new \Exception('PBKDF2 ERROR: Invalid hash algorithm');
        }

        if ($count <= 0 || $keyLength <= 0) {
            throw new \Exception('PBKDF2 ERROR: Invalid parameters.');
        }

        if (function_exists("hash_pbkdf2")) {
            // The output length is in NIBBLES (4-bits) if $raw_output is false!
            return self::pbkdf2Extension($algorithm, $password, $salt, $count, $keyLength, $rawOutput);
        }

        return self::pbkdf2Pure($algorithm, $password, $salt, $count, $keyLength, $rawOutput);
    }

    public static function pbkdf2Extension($algorithm, $password, $salt, $count, $keyLength, $rawOutput = false)
    {
        // The output length is in NIBBLES (4-bits) if $raw_output is false!
        if (!$rawOutput) {
            $keyLength = $keyLength * 2;
        }

        $hash = \hash_pbkdf2($algorithm, $password, $salt, $count, $keyLength, $rawOutput);

        return $hash;
    }

    public static function pbkdf2Pure($algorithm, $password, $salt, $count, $keyLength, $rawOutput = false)
    {

        $hashLength = strlen(hash($algorithm, "", true));
        $blockCount = ceil($keyLength / $hashLength);

        $output = "";
        for ($i = 1; $i <= $blockCount; $i++) {
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

        if ($rawOutput) {
            echo "do pure raw\n";
            $hash = substr($output, 0, $keyLength);
        } else {
            echo "do pure hex\n";
            $hash = bin2hex(substr($output, 0, $keyLength));
        }
        return $hash;
    }


    /**
     * Do HMAC hashing on $data and $salt
     *
     * @param $algo
     * @param $data
     * @param $salt
     * @return string
     */
    public static function hmac($algo, $data, $salt, $rawOutput = false)
    {
        $data = self::normalize($data);
        return hash_hmac($algo, $data, $salt, $rawOutput);
    }
}
