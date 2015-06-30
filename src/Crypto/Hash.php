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
     * Calculate Sha256(RipeMd160()) on the given data
     *
     * @param Buffer $data
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
     * @return Buffer
     */
    public static function sha256(Buffer $data)
    {
        return new Buffer(hash('sha256', $data->getBinary(), true));
    }

    /**
     * Perform SHA256 twice
     *
     * @param Buffer $data
     * @return Buffer
     */
    public static function sha256d(Buffer $data)
    {
        return new Buffer(hash('sha256', hash('sha256', $data->getBinary(), true), true));
    }

    /**
     * RIPEMD160
     *
     * @param Buffer $data
     * @return Buffer
     */
    public static function ripemd160(Buffer $data)
    {
        return new Buffer(hash('ripemd160', $data->getBinary(), true));
    }

    /**
     * RIPEMD160 twice
     *
     * @param Buffer $data
     * @return Buffer
     */
    public static function ripemd160d(Buffer $data)
    {
        return new Buffer(hash('ripemd160', hash('ripemd160', $data->getBinary(), true), true));
    }

    /**
     * Calculate a SHA1 hash
     *
     * @param Buffer $data
     * @return Buffer
     */
    public static function sha1(Buffer $data)
    {
        return new Buffer(hash('sha1', $data->getBinary(), true));
    }

    /**
     * PBKDF2
     *
     * @param string $algorithm
     * @param Buffer $password
     * @param Buffer $salt
     * @param integer $count
     * @param integer $keyLength
     * @return Buffer
     * @throws \Exception
     */
    public static function pbkdf2($algorithm, Buffer $password, Buffer $salt, $count, $keyLength)
    {
        $algorithm  = strtolower($algorithm);

        if (!in_array($algorithm, hash_algos(), true)) {
            throw new \Exception('PBKDF2 ERROR: Invalid hash algorithm');
        }

        if ($count <= 0 || $keyLength <= 0) {
            throw new \Exception('PBKDF2 ERROR: Invalid parameters.');
        }

        return new Buffer(\hash_pbkdf2($algorithm, $password->getBinary(), $salt->getBinary(), $count, $keyLength, true));
    }

    /**
     * @param Buffer $data
     * @param int $seed
     * @return Buffer
     */
    public static function murmur3(Buffer $data, $seed)
    {
        return new Buffer(pack("N", base_convert(murmurhash3($data->getBinary(), (int)$seed), 32, 10)));
    }

    /**
     * Do HMAC hashing on $data and $salt
     *
     * @param string $algo
     * @param Buffer $data
     * @param Buffer $salt
     * @return Buffer
     */
    public static function hmac($algo, Buffer $data, Buffer $salt)
    {
        return new Buffer(hash_hmac($algo, $data->getBinary(), $salt->getBinary(), true));
    }
}
