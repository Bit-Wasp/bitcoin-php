<?php

namespace BitWasp\Bitcoin\Crypto;

use BitWasp\Buffertools\Buffer;

/**
 * Class Hash
 * @package Bitcoin
 */
class Hash
{
    /**
     * Normalize data so it is always a string
     *
     * @param Buffer|string $data
     * @return string
     */
    public static function normalize($data)
    {
        if ($data instanceof Buffer) {
            $data = $data->getBinary();
        }

        return $data;
    }

    /**
     * Calculate Sha256(RipeMd160()) on the given data
     *
     * @param Buffer $data
     * @param bool $binaryOutput
     * @return Buffer
     */
    public static function sha256ripe160(Buffer $data)
    {
        return new Buffer(hash('ripemd160', hash('sha256', $data->getBinary(), true), true));
    }

    /**
     * Perform SHA256
     *
     * @param Buffer $data
     * @param bool $binaryOutput
     * @return Buffer
     */
    public static function sha256(Buffer $data)
    {
        return new Buffer(hash('sha256', $data->getBinary(), true));
    }

    /**
     * Perform SHA256 twice
     *
     * @param Buffer $data       Buffer or hex string
     * @return Buffer
     */
    public static function sha256d(Buffer $data)
    {
        return new Buffer(hash('sha256', hash('sha256', $data->getBinary(), true), true));
    }

    /**
     * RIPEMD160
     *
     * @param Buffer|string $data
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
     * @param Buffer|string $data
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
     * @param Buffer|string $data
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
     * @param Buffer|string $password
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

        // The output length is in NIBBLES (4-bits) if $raw_output is false!
        if (!$rawOutput) {
            $keyLength = $keyLength * 2;
        }

        $hash = \hash_pbkdf2($algorithm, $password, $salt, $count, $keyLength, $rawOutput);

        return $hash;
    }

    /**
     * Do HMAC hashing on $data and $salt
     *
     * @param $algo
     * @param Buffer|string $data
     * @param $salt
     * @param bool $rawOutput
     * @return string
     */
    public static function hmac($algo, $data, $salt, $rawOutput = false)
    {
        $data = self::normalize($data);
        return hash_hmac($algo, $data, $salt, $rawOutput);
    }
}
