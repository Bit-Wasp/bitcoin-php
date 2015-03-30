<?php

namespace BitWasp\Bitcoin;

class Buffer
{
    /**
     * @var int|double
     */
    protected $size;

    /**
     * @var string
     */
    protected $buffer;

    /**
     * @param string $byteString
     * @param null|integer $byteSize
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
     * @param integer $bitSize
     * @return Buffer
     * @throws \Exception
     */
    public static function hex($hex = '', $bitSize = null)
    {
        $buffer = pack("H*", $hex);
        return new self($buffer, $bitSize);
    }

    /**
     * @param integer $start
     * @param integer|null $end
     * @return Buffer
     * @throws \Exception
     */
    public function slice($start, $end = null)
    {
        $binary = $this->getBinary();
        $length = strlen($binary);
        if ($start > $length) {
            throw new \Exception('Start exceeds buffer length');
        }

        if ($end === null) {
            return new self(substr($binary, $start));
        }

        if ($end > $length) {
            throw new \Exception('Length exceeds buffer length');
        }

        $binary = substr($binary, $start, $end);
        return new self($binary);
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
     * @return string
     */
    public function getBinary()
    {
        return $this->buffer;
    }

    /**
     * @return string
     */
    public function getHex()
    {
        return bin2hex($this->getBinary());
    }

    /**
     * @return int|string
     */
    public function getInt()
    {
        return Bitcoin::getMath()->hexDec($this->getHex());
    }

    /**
     * @return Buffer
     * @throws \Exception
     */
    public function getVarInt()
    {
        return Parser::numToVarInt($this->getSize());
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
     * @return string
     */
    public function __toString()
    {
        return unpack("H*", $this->buffer)[1];
    }
}
