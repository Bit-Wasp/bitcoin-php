<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 20/11/14
 * Time: 05:19
 */

namespace Bitcoin;


class Base58 {

    private static $base58chars = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

    /**
     * Calculate a checksum for the given data
     *
     * @param $data
     * @return string
     */
    public static function checksum($data)
    {
        $hash = Hash::sha256d($data);
        $checksum = substr($hash, 0, 8);

        return $checksum;
    }

    /**
     * Encode a given hex string in base58
     *
     * @param $data
     * @return string
     * @throws \Exception
     */
    public static function encode($data)
    {
        if (Math::mod(strlen($data), 2) != 0 || strlen($data) == 0) {
            throw new \Exception('Invalid length, must be a valid hex string');
        }

        $decimal = Math::hexDec($data);
        $output = '';

        while(Math::cmp($decimal, 0) > 0) {
            list($decimal, $remain) = Math::div_qr($decimal, 58);
            $output .= substr(self::$base58chars, $remain, 1);
        }

        for ($i = 0; $i < strlen($data) && substr($data, $i, 2) == '00'; $i += 2) {
            $output .= substr(self::$base58chars, 0, 1);
        }

        $output = strrev($output);

        return $output;
    }

    /**
     * Encode the given data in base58, with a checksum to check integrity.
     *
     * @param $data
     * @return string
     * @throws \Exception
     */
    public static function encode_check($data)
    {
        $checksum = self::checksum($data);
        $hex = $data . $checksum;
        return self::encode($hex);
    }

    /**
     * Decode a base58 string
     *
     * @param $base58
     * @return string
     */
    public static function decode($base58)
    {
        $original = $base58;
        $return = 0;

        for ($i = 0; $i < strlen($base58); $i++) {
            $return = Math::add(Math::mul($return, 58), strpos(self::$base58chars, $base58[$i]));
        }

        $hex = Math::decHex($return);

        for ($i = 0; $i < strlen($original) && $original[$i] == "1"; $i++) {
            $hex = "00" . $hex;
        }

        if (Math::mod(strlen($return),2) != 0 ) {
            $hex = "0" . $return;
        }

        return $hex;
    }

    /**
     * Decode a base58 checksum string and validate checksum
     *
     * @param $base58
     * @return string
     * @throws Base58ChecksumFailure
     */
    public static function decode_check($base58)
    {

        $hex = self::decode($base58);
        $data = substr($hex, 0, -8);
        $cs_verify = substr($hex, -8);

        $checksum = self::checksum($data);

        if ($checksum != $cs_verify) {
            throw new Base58ChecksumFailure('Failed to verify checksum');
        }

        return $data;
    }
}

class Base58ChecksumFailure extends \Exception {};