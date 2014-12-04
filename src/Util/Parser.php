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
     * @param null $input
     * @throws \Exception
     */
    public function __construct($input = null)
    {
        if (!$input instanceof Buffer) {
            $input = Buffer::hex($input);
        }

        $this->string   = $input->serialize();
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
       /* $parsed = new Parser();

        if (Math::cmp($decimal, 0xfd) < 0) {
            $parsed->writeInt(1, $decimal);

        } else if (Math::cmp($decimal, Math::pow(2, 16)) < 1) {
            $parsed->writeInt(1, 0xfd)
                ->writeInt(2, $decimal, true);

        } else if (Math::cmp($decimal, Math::sub(Math::pow(2, 32),1)) < 1) {
            $parsed->writeInt(1, 0xfe)
                ->writeInt(4, $decimal, true);

        } else if (Math::cmp($decimal, Math::sub(Math::pow(2, 64),1)) < 1) {
            echo "$decimal\n";
            $parsed->writeInt(1, 0xff)
                ->writeInt(8, $decimal, true);
        } else {
            throw new \Exception('Number too big');
        }

        return $parsed->getBuffer();*/

        if ($decimal < 0xfd) {
            $bin = chr($decimal);
        } else if ($decimal <= 0xffff) {                     // Uint16
            $bin = pack("Cv", 0xfd, $decimal);
        } else if ($decimal <= 0xffffffff) {                 // Uint32
            $bin = pack("CV", 0xfe, $decimal);
        } else { //if (Math::cmp($decimal, Math::pow(2, 64)) < 1) {        // Uint64
            /*$highMap = 0xffffffff00000000;
            $lowMap  = 0x00000000ffffffff;
            $higher  = ($decimal & $highMap) >>32;
            $lower   = $decimal & $lowMap;
            $bin     = pack('CNN', 0xff, $higher, $lower);*/

            /* if (version_compare(phpversion(), '5.6.3') >= 0) {
                 $bin = pack("CP", 0xff, $decimal);
             } else { */

            // Todo, support for 64bit integers
             throw new \Exception('numToVarInt(): Integer too large');
             /*}*/
            //}
        }

        return new Buffer($bin);
    }


    /**
     * Get the position pointer of the parser - ie, how many bytes from 0
     *
     * @return int
     */
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
     * Parse $bytes bytes from the string, and return the obtained buffer
     *
     * @param $bytes
     * @param bool $flipBytes
     * @return Buffer
     * @throws \Exception
     */
    public function readBytes($bytes, $flipBytes = false)
    {
        $string = substr($this->string, $this->getPosition(), $bytes);
        $length = strlen($string);

        if ($length == 0) {
            return false;
        } else if ($length !== $bytes) {
            throw new \Exception('Could not parse string of required length');
        }

        $this->position += $bytes;

        if ($flipBytes) {
            $string = $this->flipBytes($string);
        }

        $buffer = new Buffer($string);
        return $buffer;
    }

    /**
     * Write $data as $bytes bytes. Can be flipped if needed.
     *
     * @param $bytes
     * @param $data
     * @param bool $flipBytes
     * @return $this
     */
    public function writeBytes($bytes, $data, $flipBytes = false)
    {
        // Create a new buffer, ensuring that were within the limit set by $bytes
        if ($data instanceof Buffer) {
            $newBuffer = new Buffer($data->serialize(), $bytes);
        } else {
            $newBuffer = Buffer::hex($data, $bytes);
        }

        $data = $newBuffer->serialize();

        if ($flipBytes) {
            $data = $this->flipBytes($data);
        }

        $this->string .= $data;
        return $this;
    }

    /**
     * Write with length - Writes a buffer and prefixes it with it's length,
     * as a VarInt
     *
     * @param Buffer $buffer
     * @return $this
     * @throws \Exception
     */
    public function writeWithLength(Buffer $buffer)
    {
        $varInt = self::numToVarInt($buffer->getSize());
        $buffer = new Buffer($varInt->serialize() .  $buffer->serialize());
        $this->writeBytes($buffer->getSize(), $buffer);
        return $this;
    }

    /**
     * Get array. TODO. Should parse a varint and apply a closure
     *
     * @param $array
     * @param callable $callback
     * @return array
     */
    public function getArray($array, callable $callback)
    {
        $results = array();
        array_walk($array, function ($value) use ($callback, &$results) {
            var_dump($value);
            //$results[] = $callback($value);
        });
        return $results;
    }

    /**
     * Take an array containing serializable objects.
     * @param $serializable
     * @return $this
     */
    public function writeArray($serializable)
    {
        $varInt = self::numToVarInt(count($serializable));

        $parser = new Parser($varInt);
        //$parser->writeInt(1, count($serializable));

        foreach ($serializable as $object) {
            if (!in_array('Bitcoin\SerializableInterface', class_implements($object))) {
                throw new \RuntimeException('Objects being serialized to an array must implement the SerializableInterface');
            }
            $buffer = new Buffer($object->serialize());
            $parser->writeBytes($buffer->getSize(), $buffer);
        }

        $this->string .= $parser->getBuffer()->serialize();

        return $this;
    }

    /**
     * Write an integer to the buffer
     *
     * @param $bytes
     * @param $int
     * @param bool $flipBytes
     * @return $this
     */
    public function writeInt($bytes, $int, $flipBytes = false)
    {
        $hex  = str_pad(Math::decHex($int), $bytes*2, '0', STR_PAD_LEFT);
        $data = pack("H*", $hex);

        if ($flipBytes) {
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
     * Extract $bytes from the parser, and return them as a new Parser instance.
     *
     * @param $bytes
     * @param bool $flipBytes
     * @return Parser
     * @throws \Exception
     */
    public function parseBytes($bytes, $flipBytes = false)
    {
        $buffer = $this->readBytes($bytes, $flipBytes);
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
        $byte   = $this->readBytes(1);
        $int    = $byte->serialize('int');

        if (Math::cmp($int, 0xfd) < 0) {
            return $byte;
        } else if (Math::cmp($int, 0xfd) == 0) {
            return $this->readBytes(2, true);
        } else if (Math::cmp($int, 0xfe) == 0) {
            return $this->readBytes(4, true);
        } else if (Math::cmp($int, 0xff) == 0) {
            return $this->readBytes(8, true);
        }
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
        $varInt = $this->getVarInt()->serialize('int');
        $string = $this->readBytes($varInt);
        return $string;
    }
}
