<?php

namespace BitWasp\Bitcoin;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure;
use BitWasp\Bitcoin\Exceptions\Base58InvalidCharacter;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;

class Base58
{
    /**
     * @var string
     */
    private static $base58chars = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    /**
     * Encode a given hex string in base58
     *
     * @param BufferInterface $buffer
     * @return string
     * @throws \Exception
     */
    public static function encode(BufferInterface $buffer)
    {
        $size = $buffer->getSize();
        if ($buffer->getBinary() === '') {
            return '';
        }

        $math = Bitcoin::getMath();

        $orig = $buffer->getBinary();
        $decimal = $buffer->getGmp();

        $return = '';
        $zero = gmp_init(0);
        $_58 = gmp_init(58);
        while ($math->cmp($decimal, $zero) > 0) {
            list($decimal, $rem) = $math->divQr($decimal, $_58);
            $return .= self::$base58chars[(int) gmp_strval($rem, 10)];
        }
        $return = strrev($return);

        //leading zeros
        for ($i = 0; $i < $size && $orig[$i] === "\x00"; $i++) {
            $return = '1' . $return;
        }

        return $return;
    }

    /**
     * Decode a base58 string
     * @param string $base58
     * @return Buffer
     * @throws Base58InvalidCharacter
     */
    public static function decode($base58)
    {
        $math = Bitcoin::getMath();
        if ($base58 === '') {
            return new Buffer('', 0, $math);
        }

        $original = $base58;
        $length = strlen($base58);
        $return = gmp_init(0);
        $_58 = gmp_init(58);
        for ($i = 0; $i < $length; $i++) {
            $loc = strpos(self::$base58chars, $base58[$i]);
            if ($loc === false) {
                throw new Base58InvalidCharacter('Found character that is not allowed in base58: ' . $base58[$i]);
            }
            $return = $math->add($math->mul($return, $_58), gmp_init($loc, 10));
        }

        $binary = $math->cmp($return, gmp_init(0)) === 0 ? '' : Buffer::int(gmp_strval($return, 10))->getBinary();
        for ($i = 0; $i < $length && $original[$i] === '1'; $i++) {
            $binary = "\x00" . $binary;
        }

        return new Buffer($binary);
    }

    /**
     * Calculate a checksum for the given data
     *
     * @param BufferInterface $data
     * @return BufferInterface
     */
    public static function checksum(BufferInterface $data)
    {
        return Hash::sha256d($data)->slice(0, 4);
    }

    /**
     * Decode a base58 checksum string and validate checksum
     *
     * @param string $base58
     * @return BufferInterface
     * @throws Base58ChecksumFailure
     */
    public static function decodeCheck($base58)
    {
        $hex = self::decode($base58);
        $data = $hex->slice(0, -4);
        $csVerify = $hex->slice(-4);

        if (self::checksum($data)->getBinary() !== $csVerify->getBinary()) {
            throw new Base58ChecksumFailure('Failed to verify checksum');
        }

        return $data;
    }

    /**
     * Encode the given data in base58, with a checksum to check integrity.
     *
     * @param BufferInterface $data
     * @return string
     * @throws \Exception
     */
    public static function encodeCheck(BufferInterface $data)
    {
        return self::encode(Buffertools::concat($data, self::checksum($data)));
    }
}
