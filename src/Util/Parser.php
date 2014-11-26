<?php

namespace Bitcoin\Util;

/**
 * Class Parser - mainly for decoding transactions..
 *
 * @package Bitcoin
 */
class Parser
{
    /**
     * @var string
     */
    protected $string;

    /**
     * @var int
     */
    protected $position;

    /**
     * @param null $in
     * @throws \Exception
     */
    public function __construct($in = null)
    {
        // Make sure we're dealing only with binary data
        if ($in instanceof Buffer) {
            $this->string = $in->serialize();
        } else {
            $buffer = Buffer::hex($in);
            $this->string = $buffer->serialize();
        }

        $this->postion = 0;
        return $this;
    }

    // Functions for serializing data in object to string

    /**
     * Write $data as $bytes bytes. Can be flipped if needed.
     *
     * @param $bytes
     * @param $data
     * @param bool $flip_bytes
     * @return $this
     */
    public function writeBytes($bytes, $data, $flip_bytes = false)
    {
        // Create a new buffer, ensuring that were within the limit set by $bytes
        if ($data instanceof Buffer) {
            $newBuffer = new Buffer($data->serialize(), $bytes);
        } else {
            $newBuffer = Buffer::hex($data, $bytes);
        }

        $data = $newBuffer->serialize();

        if ($flip_bytes) {
            $data = $this->flipBytes($data);
        }

        $this->string .= $newBuffer->serialize();
        return $this;
    }

    /**
     * @param $bytes
     * @param $int
     * @param bool $flip_bytes
     */
    public function writeInt($bytes, $int, $flip_bytes = false)
    {
        $hex  = Math::decHex($int);
        $hex  = str_pad($hex, $bytes*2, '0', STR_PAD_LEFT);
        $data = pack("H*", $hex);

        if ($flip_bytes) {
            $data = $this->flipBytes($data);
        }

        $this->string .= $data;
        return $this;
    }

    /**
     * Flip byte order of this binary string
     *
     * @param $hex
     * @return string
     */
    public static function flipBytes($hex)
    {
        return implode('', array_reverse(str_split($hex, 1)));
    }

    /**
     * Return the string as a buffer
     *
     * @return Buffer
     */
    public function getBuffer()
    {
        $buffer = new Buffer($this->string);
        return $buffer;
    }

    // Functions for pulling data from string

    /**
     * Parse $bytes bytes from the string, and return the obtained buffer
     *
     * @param $bytes
     * @param bool $flip_bytes
     * @return string
     */
    public function readBytes($bytes, $flip_bytes = false)
    {
        $string = substr($this->string, $this->position, $bytes);
        $length = strlen($string);
        if ($length == 0) {
            return false;
        } else if ($length !== $bytes) {
            throw new Exception('Could not parse string of required length');
        }

        $this->position += $bytes;

        if ($flip_bytes) {
            $string = $this->flipBytes($string);
        }

        $buffer = new Buffer($string);

        return $buffer;
    }

    public function getVarInt()
    {

    }

    public function getArray($numIndexes)
    {

    }
}
