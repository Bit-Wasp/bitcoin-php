<?php

namespace BitWasp\Bitcoin;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure;
use BitWasp\Buffertools\Buffer;

class Base58
{
    /**
     * @var string
     */
    private static $base58chars = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

    /**
     * Encode a given hex string in base58
     *
     * @param Buffer $binary
     * @return string
     * @throws \Exception
     */
    public static function encode(Buffer $binary)
    {
        $size = $binary->getSize();
        if ($size == 0) {
            return '';
        }

        $math = Bitcoin::getMath();

        $orig = $binary->getBinary();
        $decimal = $binary->getInt();

        $return = "";
        while ($math->cmp($decimal, 0) > 0) {
            list($decimal, $rem) = $math->divQr($decimal, 58);
            $return = $return . self::$base58chars[$rem];
        }
        $return = strrev($return);

        //leading zeros
        for ($i = 0; $i < $size && $orig[$i] == "\x00"; $i++) {
            $return = "1" . $return;
        }

        return $return;
    }

    /**
     * Decode a base58 string
     *
     * @param $base58
     * @return Buffer
     */
    public static function decode($base58)
    {
        if (strlen($base58) == 0) {
            return new Buffer();
        }

        $original = $base58;
        $strlen = strlen($base58);
        $return = '0';
        $math = Bitcoin::getMath();

        for ($i = 0; $i < $strlen; $i++) {
            $return = $math->add($math->mul($return, 58), strpos(self::$base58chars, $base58[$i]));
        }

        $hex = ($return == '0') ? '' : $math->decHex($return);

        for ($i = 0; $i < $strlen && $original[$i] == "1"; $i++) {
            $hex = "00" . $hex;
        }

        $buffer = Buffer::hex($hex);
        return $buffer;
    }

    /**
     * Calculate a checksum for the given data
     *
     * @param $data
     * @return Buffer
     */
    public static function checksum(Buffer $data)
    {
        return Hash::sha256d($data)->slice(0, 4);
    }

    /**
     * Decode a base58 checksum string and validate checksum
     *
     * @param $base58
     * @return Buffer
     * @throws Base58ChecksumFailure
     */
    public static function decodeCheck($base58)
    {
        $hex = self::decode($base58);
        $csVerify = $hex->slice(-4);
        $data = $hex->slice(0, -4);
        $checksum = self::checksum($data);

        if ($checksum != $csVerify) {
            throw new Base58ChecksumFailure('Failed to verify checksum');
        }

        return $data;
    }

    /**
     * Encode the given data in base58, with a checksum to check integrity.
     *
     * @param $data
     * @return string
     * @throws \Exception
     */
    public static function encodeCheck(Buffer $data)
    {
        $checksum = self::checksum($data);
        $data = Buffer::hex($data->getHex() . $checksum->getHex());
        return self::encode($data);
    }
}
