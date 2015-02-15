<?php

namespace Afk11\Bitcoin;

use \Afk11\Bitcoin\Bitcoin;

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
     * @param $byteString
     * @param null $byteSize
     * @throws \Exception
     */
    public function __construct($byteString = '', $byteSize = null)
    {
        if (is_numeric($byteSize)) {
            // Check the integer doesn't overflow its supposed size
            if (Bitcoin::getMath()->cmp(strlen($byteString), $byteSize) > 0) {
                throw new \Exception('Byte string exceeds maximum size');
            }
        }

        $this->size   = $byteSize;
        $this->buffer = $byteString;
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
     * @param null $bitSize
     * @return Buffer
     * @throws \Exception
     */
    public static function hex($hex = '', $bitSize = null)
    {
        $buffer = pack("H*", $hex);
        return new self($buffer, $bitSize);
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
        } elseif ($type == 'int') {
            $hex = $this->__toString();
            return Bitcoin::getMath()->hexDec($hex);
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
