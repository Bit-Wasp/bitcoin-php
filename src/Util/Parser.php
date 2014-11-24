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

    protected $parsed = array();

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
    }

    // Functions for serializing data in object to string
    /**
     * @param $bitsize
     * @param Buffer $buffer
     * @param bool $flip_bytes
     */
    public function writeBytes($bytes, $decimal, $flip_bytes = false)
    {
        $hex = Math::decHex($decimal);
        if ($flip_bytes) {
            $hex = $this->flipBytes($hex);
        }

        // Do this to ensure size constraint is met.
        $newBuffer = Buffer::hex($hex, $bytes);
        $this->string .= $newBuffer->serialize();
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
     * @return Buffer
     */
    public function getBuffer()
    {
        $buffer = new Buffer($this->string);
        return $buffer;
    }
    /**
     * @param $bit
     * @param bool $flip_bytes
     * @return string
     */
    public function getBinaryString($bytes, $flip_bytes = false)
    {
        $string = substr($this->string, $this->position, $bytes);
        $this->position += $bytes;

        if ($flip_bytes) {
            $string = $this->flipBytes($string);
        }

        $buffer = new Buffer($string);
        return $buffer;
    }

    // Functions for pulling data from string
    public function readBytes($bit, $flip_bytes = false)
    {
        $string = $this->getBinaryString($bit, $flip_bytes);
        return $string;
    }

    public function readBytesHex($bit, $flip_bytes = false)
    {
        $string = $this->readBytes($bit, $flip_bytes);
        $hex    = $string->serialize('hex');
        return $hex;

    }

    public function getVarInt()
    {

    }

    public function getArray($numIndexes)
    {

    }
}
