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

            // Check the integer doesn't overflow its supposed site
            if (Math::cmp(strlen($byte_string), $byte_size) > 0) {
                throw new \Exception('Byte string exceeds maximum size');
            }
        }

        $this->size = $byte_size;
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
        } else if ($type == 'int') {
            $hex = $this->__toString();
            return (int)Math::hexDec($hex);
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
