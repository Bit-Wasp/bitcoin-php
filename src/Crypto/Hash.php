<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto;

use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use lastguest\Murmur;

class Hash
{
    /**
     * Calculate Sha256(RipeMd160()) on the given data
     *
     * @param BufferInterface $data
     * @return BufferInterface
     */
    public static function sha256ripe160(BufferInterface $data): BufferInterface
    {
        return new Buffer(hash('ripemd160', hash('sha256', $data->getBinary(), true), true), 20);
    }

    /**
     * Perform SHA256
     *
     * @param BufferInterface $data
     * @return BufferInterface
     */
    public static function sha256(BufferInterface $data): BufferInterface
    {
        return new Buffer(hash('sha256', $data->getBinary(), true), 32);
    }

    /**
     * Perform SHA256 twice
     *
     * @param BufferInterface $data
     * @return BufferInterface
     */
    public static function sha256d(BufferInterface $data): BufferInterface
    {
        return new Buffer(hash('sha256', hash('sha256', $data->getBinary(), true), true), 32);
    }

    /**
     * RIPEMD160
     *
     * @param BufferInterface $data
     * @return BufferInterface
     */
    public static function ripemd160(BufferInterface $data): BufferInterface
    {
        return new Buffer(hash('ripemd160', $data->getBinary(), true), 20);
    }

    /**
     * RIPEMD160 twice
     *
     * @param BufferInterface $data
     * @return BufferInterface
     */
    public static function ripemd160d(BufferInterface $data): BufferInterface
    {
        return new Buffer(hash('ripemd160', hash('ripemd160', $data->getBinary(), true), true), 20);
    }

    /**
     * Calculate a SHA1 hash
     *
     * @param BufferInterface $data
     * @return BufferInterface
     */
    public static function sha1(BufferInterface $data): BufferInterface
    {
        return new Buffer(hash('sha1', $data->getBinary(), true), 20);
    }

    /**
     * PBKDF2
     *
     * @param string $algorithm
     * @param BufferInterface $password
     * @param BufferInterface $salt
     * @param integer $count
     * @param integer $keyLength
     * @return BufferInterface
     * @throws \Exception
     */
    public static function pbkdf2(string $algorithm, BufferInterface $password, BufferInterface $salt, int $count, int $keyLength): BufferInterface
    {
        if ($keyLength < 0) {
            throw new \InvalidArgumentException('Cannot have a negative key-length for PBKDF2');
        }

        $algorithm  = strtolower($algorithm);

        if (!in_array($algorithm, hash_algos(), true)) {
            throw new \Exception('PBKDF2 ERROR: Invalid hash algorithm');
        }

        if ($count <= 0 || $keyLength <= 0) {
            throw new \Exception('PBKDF2 ERROR: Invalid parameters.');
        }

        return new Buffer(\hash_pbkdf2($algorithm, $password->getBinary(), $salt->getBinary(), $count, $keyLength, true), $keyLength);
    }

    /**
     * @param BufferInterface $data
     * @param int $seed
     * @return BufferInterface
     */
    public static function murmur3(BufferInterface $data, int $seed): BufferInterface
    {
        return new Buffer(pack('N', Murmur::hash3_int($data->getBinary(), $seed)), 4);
    }

    /**
     * Do HMAC hashing on $data and $salt
     *
     * @param string $algo
     * @param BufferInterface $data
     * @param BufferInterface $salt
     * @return BufferInterface
     */
    public static function hmac(string $algo, BufferInterface $data, BufferInterface $salt): BufferInterface
    {
        return new Buffer(hash_hmac($algo, $data->getBinary(), $salt->getBinary(), true));
    }
}
