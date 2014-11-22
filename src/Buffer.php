<?php

namespace Bitcoin;

/**
 * Class Buffer
 * @package Bitcoin
 */
class Buffer
{
    /**
     * @var int|null
     */
    protected $size;

    /**
     * @var string
     */
    protected $buffer;

    /**
     * @param $byte_string
     * @param null $bitsize
     * @throws \Exception
     */
    public function __construct($byte_string, $bitsize = null)
    {
        if ( ! is_string($byte_string)) {
            throw new \Exception('Data for buffer must be a string');
        }

        if (is_numeric($bitsize)) {
            $max_int = Math::pow(2, $bitsize);

            if (Math::cmp($byte_string, $max_int) > 0) {
                throw new \Exception('Byte string exceeds maximum size');
            }
        }

        $this->size = $bitsize;
        $this->buffer = $byte_string;
    }

    /**
     * @return int|null|string
     */
    public function getMaxSize()
    {
        return $this->size;
    }

    /**
     * @param $hex
     * @param null $uint
     * @return Buffer
     * @throws \Exception
     */
    public static function hex($hex, $uint = null)
    {
        if (ctype_print($hex) AND ctype_xdigit($hex) == false) {
            throw new \Exception('Hex buffer must contain hexadecimal chars');
        }

        $buffer = pack("H*", $hex);
        return new self($buffer, $uint);
    }

    /**
     * @param string|null $type
     * @return int
     */
    public function getSize($type = null)
    {
        $string = $this->serialize($type);
        $size   = strlen($string);
        return $size;
    }

    /**
     * @param string|null $type
     * @return string
     */
    public function serialize($type = null)
    {
        if ($type == 'hex') {
            return $this->__toString();
        } else {
            return $this->buffer;
        }
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        $unpack = unpack("H*", $this->buffer);
        return $unpack[1];
    }

} 