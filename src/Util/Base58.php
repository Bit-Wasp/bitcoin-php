<?php

namespace Bitcoin\Util;

use Bitcoin\Crypto\Hash;
use Bitcoin\Exceptions\Base58ChecksumFailure;

/**
 * Class Base58
 * @package Bitcoin
 */
class Base58
{
    /**
     * @var string
     */
    private static $base58chars = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

    /**
     * Encode a given hex string in base58
     *
     * @param $hex
     * @return string
     * @throws \Exception
     */
    public static function encode($hex)
    {
        if ($hex == '') {
            return '';
        }

        if (Math::mod(strlen($hex), 2) !== '0') {
            throw new \Exception('Data must be of even length');
        }
        $origHex = $hex;

        $decimal = Math::hexDec($hex);
        $return = "";
        while (Math::cmp($decimal, 0) > 0) {
            list($decimal, $rem) = Math::divQr($decimal, 58);
            $return = $return . self::$base58chars[$rem];
        }
        $return = strrev($return);

        //leading zeros
        for ($i = 0; $i < strlen($origHex) && substr($origHex, $i, 2) == "00"; $i += 2) {
            $return = "1" . $return;
        }
        return $return;
    }

    /**
     * Decode a base58 string
     *
     * @param $base58
     * @return string
     */
    public static function decode($base58)
    {
        if (strlen($base58) == 0) {
            return '';
        }

        $original = $base58;
        $return = '0';

        for ($i = 0; $i < strlen($base58); $i++) {
            $return = Math::add(Math::mul($return, 58), strpos(self::$base58chars, $base58[$i]));
        }

        $hex = ($return == '0') ? '' : Math::decHex($return);

        for ($i = 0; $i < strlen($original) && $original[$i] == "1"; $i++) {
            $hex = "00" . $hex;
        }

        if (Math::mod(strlen($hex), 2) !== '0') {
            $hex = "0" . $hex;
        }

        return $hex;
    }

    /**
     * Calculate a checksum for the given data
     *
     * @param $data
     * @return string
     */
    public static function checksum($data)
    {
        $data = pack("H*", $data);
        $hash = Hash::sha256d($data);
        $checksum = substr($hash, 0, 8);

        return $checksum;
    }

    /**
     * Decode a base58 checksum string and validate checksum
     *
     * @param $base58
     * @return string
     * @throws Base58ChecksumFailure
     */
    public static function decodeCheck($base58)
    {
        $hex       = self::decode($base58);
        $csVerify  = substr($hex, -8);
        $data      = substr($hex, 0, -8);

        $checksum  = self::checksum($data);

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
    public static function encodeCheck($data)
    {
        $checksum = self::checksum($data);
        $hex = $data . $checksum;
        return self::encode($hex);
    }
}
