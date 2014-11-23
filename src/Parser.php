<?php

namespace Bitcoin;

/**
 * Class Parser
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
    public function __construct(Buffer $in = null)
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
    public function writeUint($bit, Buffer $buffer, $flip_bytes = false)
    {
        $bytes = $bit / 2;
        $data = $buffer->serialize();
        if ($flip_bytes) {
            $data = $this->flipBytes($data);
        }

        // Do this to ensure size constraint is met.
        $newBuffer = new Buffer($data, $bytes);
        $this->string .= $newBuffer->serialize();

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
    public function getBinaryString($bit, $flip_bytes = false)
    {
        $bytes = $bit / 2;
        $string = substr($this->string, $this->position, $bytes);

        if ($flip_bytes) {
            $string = $this->flipBytes($string);
        }

        $buffer = new Buffer($string);
        return $buffer;
    }

    // Functions for pulling data from string
    public function getUint($bit, $flip_bytes = false)
    {
        $string = $this->getBinaryString($bit, $flip_bytes);

        return $string;

    }

    public function getVarInt()
    {

    }

    public function getArray($numIndexes)
    {

    }

}