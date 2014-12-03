<?php

namespace Bitcoin\Util;

use Bitcoin\Util\Buffer;

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
    protected $position = 0;

    /**
     * Instantiate class, optionally taking a given hex string or Buffer.
     *
     * @param null $in
     * @throws \Exception
     */
    public function __construct($in = null)
    {
        if ($in instanceof Buffer) {
            $this->string = $in->serialize();
        } else {
            $buffer = Buffer::hex($in);
            $this->string = $buffer->serialize();
        }

        $this->position = 0;
        return $this;
    }

    /**
     * Convert a decimal number into a VarInt Buffer
     *
     * @param $decimal
     * @return string
     * @throws \Exception
     */
    public static function numToVarInt($decimal)
    {

        if ($decimal < 0xfd) {
            $bin = chr($decimal);
        } else if ($decimal <= 0xff) {                     // Uint16
            $bin = pack("Cv", 0xfd, $decimal);
        } else if ($decimal <= 0xffff) {                 // Uint32
            $bin = pack("CV", 0xfe, $decimal);
        } else { //if ($decimal < 0xfffffffff) {        // Uint64
            //  if (version_compare(phpversion(), '5.6.0') >= 0) {
            //      return pack("CP", 0xff, $decimal);
            //  } else {
            throw new \Exception('numToVarInt(): Integer too large');
            //  }
        }

        return new Buffer($bin);
    }



    public function getPosition()
    {
        return $this->position;
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

        $this->string .= $data;
        return $this;
    }

    public function writeWithLength(Buffer $buffer)
    {
        $varInt = self::numToVarInt($buffer->getSize());
        $buffer = new Buffer($varInt->serialize() .  $buffer->serialize());
        $this->writeBytes($buffer->getSize(), $buffer);
        return $this;
    }

    public function writeArray($serializable)
    {
        $parser = new Parser;
        $parser->writeInt(1, count($serializable));

        $a = array();
        foreach ($serializable as $object) {
            $a[] = $object->serialize('hex');
            $buffer = new Buffer($object->serialize());
            $parser->writeBytes($buffer->getSize(), $buffer);
        }

        $this->string .= $parser->getBuffer()->serialize();
        //echo $parser->getBuffer()->serialize('hex')."\n";
        return $this;
    }

    public function getArray($array, callable $callback)
    {
        $results = array();
        array_walk($array, function($value, $key) use ($callback, &$results) {
            var_dump($value);
            //$results[] = $callback($value);
        });
        return $results;
    }

    /**
     * Write an integer to the buffer
     *
     * @param $bytes
     * @param $int
     * @param bool $flip_bytes
     * @return $this
     */
    public function writeInt($bytes, $int, $flip_bytes = false)
    {
        $hex  = str_pad(Math::decHex($int), (int)$bytes*2, '0', STR_PAD_LEFT);
        $data = pack("H*", $hex);

        if ($flip_bytes) {
            $data = $this->flipBytes($data);
        }

        $this->string .= $data;

        return $this;
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

    /**
     * Parse $bytes bytes from the string, and return the obtained buffer
     *
     * @param $bytes
     * @param bool $flip_bytes
     * @return Buffer
     * @throws \Exception
     */
    public function readBytes($bytes, $flip_bytes = false)
    {
        $string = substr($this->string, $this->getPosition(), $bytes);

        $length = strlen($string);

        if ($length == 0) {
            return false;
        } else if ($length == 0 OR $length !== $bytes) {
            throw new \Exception('Could not parse string of required length');
        }

        $this->position += $bytes;
        if ($flip_bytes) {
            $string = $this->flipBytes($string);
        }

        $buffer = new Buffer($string);
        return $buffer;
    }

    /**
     * Return a variable length string. This is a variable length string,
     * prefixed with it's length encoded as a VarInt.
     *
     * @return Buffer
     * @throws \Exception
     */
    public function getVarString()
    {
        return $this->readBytes(
            $this->getVarInt()->serialize('int')
        );
    }

    /**
     * Extract $bytes from the parser, and return them as a new Parser instance.
     *
     * @param $bytes
     * @param bool $flip_bytes
     * @return Parser
     * @throws \Exception
     */
    public function parseBytes($bytes, $flip_bytes = false)
    {
        $buffer = $this->readBytes($bytes, $flip_bytes);
        $parser = new Parser($buffer);
        return $parser;
    }

    /**
     * Parse a variable length integer
     *
     * @return Buffer
     * @throws \Exception
     */
    public function getVarInt()
    {
        // Return the length encoded in this var int
        $b    = $this->readBytes(1);
        $byte = $b->serialize('int');

        if (Math::cmp($byte, 0xfd) < 0) {
            return $b;
        } else if (Math::cmp($byte, 0xfd) == 0) {
            return $this->readBytes(1, true);
        } else if (Math::cmp($byte, 0xfe) == 0) {
            return $this->readBytes(2, true);
        } else if (Math::cmp($byte, 0xff) == 0) {
            return $this->readBytes(4, true);
        }
    }
}
