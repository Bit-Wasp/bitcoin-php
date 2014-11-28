<?php

namespace Bitcoin\Util;

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
     * @param null $byte_size
     * @throws \Exception
     */
    public function __construct($byte_string = '', $byte_size = null)
    {
        if (is_numeric($byte_size)) {
            // Check the integer doesn't overflow its supposed size
            if (Math::cmp(strlen($byte_string), $byte_size) > 0) {
                throw new \Exception('Byte string exceeds maximum size');
            }
        }

        $this->size = $byte_size;
        $this->buffer = $byte_string;
    }

    /**
     * Return the max size of this buffer
     *
     * @return int|null
     */
    public function getMaxSize()
    {
        return $this->size;
    }

    /**
     * Create a new buffer from a hex string
     * @param $hex
     * @param null $bit_size
     * @return Buffer
     * @throws \Exception
     */
    public static function hex($hex = '', $bit_size = null)
    {
        $buffer = pack("H*", $hex);
        return new self($buffer, $bit_size);
    }

    /**
     * Get the size of the buffer to be returned, depending on the $type
     *
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
     * Serialize a the buffer to hex, an integer, or a byte string
     *
     * @param string|null $type
     * @return string
     */
    public function serialize($type = null)
    {
        if ($type == 'hex') {
            return $this->__toString();
        } else if ($type == 'int') {
            $hex = $this->__toString();
            return (int)Math::hexDec($hex);
        } else {
            return $this->buffer;
        }
    }

    /**
     * Print the contents of the buffer as a string
     *
     * @return mixed
     */
    public function __toString()
    {
        $unpack = unpack("H*", $this->buffer);
        return $unpack[1];
    }
}
