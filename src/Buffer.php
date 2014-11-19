<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 18:13
 */

namespace Bitcoin;

class Buffer {

    protected $math;
    protected $size;
    protected $buffer;

    public function __construct($byte_string, $bitsize = null)
    {
        if ( ! is_string($byte_string)) {
            throw new \Exception('Number must be a string');
        }

        if (is_numeric($bitsize)) {
            $max_int = Math::pow(2, $bitsize);

            if (Math::cmp($byte_string, $max_int) > 0) {
                throw new \Exception('Decimal is out of range of bitsize');
            }
        }

        $this->size = $bitsize;
        $this->buffer = $byte_string;
    }

    public function serialize()
    {

    }

    public function __toString()
    {
        $unpack = unpack("H*", $this->buffer);
        return $unpack[1];
    }

    public static function hex($hex, $uint = null)
    {
        if (ctype_print($hex) AND ctype_xdigit($hex) == false) {
            throw new \Exception('Hex buffer must contain hexadecimal chars');
        }

        $buffer = pack("H*", $hex);
        return new self($buffer, $uint);
    }
} 