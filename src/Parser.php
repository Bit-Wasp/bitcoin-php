<?php

namespace Afk11\Bitcoin;

use \Afk11\Bitcoin\Buffer;
use \Afk11\Bitcoin\Bitcoin;
use \Afk11\Bitcoin\Exceptions\ParserOutOfRange;

class Parser
{
    /**
     * @var string
     */
    protected $string;

    /**
     * @var \Afk11\Bitcoin\Math\Math
     */
    protected $math;

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
        $this->math     = Bitcoin::getMath();
        return $this;
    }

    /**
     * Convert a decimal number into a VarInt Buffer
     *
     * @param $decimal
     * @return Buffer
     * @throws \Exception
     */
    public static function numToVarInt($decimal)
    {
        if ($decimal < 0xfd) {
            $bin = chr($decimal);
        } elseif ($decimal <= 0xffff) {
            // Uint16
            $bin = pack("Cv", 0xfd, $decimal);
        } elseif ($decimal <= 0xffffffff) {
            // Uint32
            $bin = pack("CV", 0xfe, $decimal);
        } else {
            // Todo, support for 64bit integers
            throw new \Exception('numToVarInt(): Integer too large');
        }

        return new Buffer($bin);
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
     * Get the position pointer of the parser - ie, how many bytes from 0
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Parse a vector from a string of data. Vectors are arrays, prefixed
     * by the number of items in the list.
     *
     * @param callable $callback
     * @return array
     */
    public function getArray(callable $callback)
    {
        $results = array();
        $varInt  = $this->getVarInt();
        $txCount = $varInt->serialize('int');

        for ($i = 0; $i < $txCount; $i++) {
            $results[] = $callback($this);
        }

        return $results;
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

        if ($this->math->cmp($int, 0xfd) < 0) {
            return $byte;
        } elseif ($this->math->cmp($int, 0xfd) == 0) {
            return $this->readBytes(2, true);
        } elseif ($this->math->cmp($int, 0xfe) == 0) {
            return $this->readBytes(4, true);
        } elseif ($this->math->cmp($int, 0xff) == 0) {
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
        if ($this->math->cmp($varInt, 0) == 0) {
            return new Buffer();
        }
        $string = $this->readBytes($varInt);
        return $string;
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
        } elseif ($this->math->cmp($length, $bytes) !== 0) {
            throw new ParserOutOfRange('Could not parse string of required length');
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
     * Take an array containing serializable objects.
     * @param $serializable
     * @return $this
     */
    public function writeArray($serializable)
    {
        $varInt = self::numToVarInt(count($serializable));

        $parser = new Parser($varInt);

        foreach ($serializable as $object) {
            if (!in_array('Bitcoin\SerializableInterface', class_implements($object))) {
                throw new \RuntimeException('Objects being serialized to an array must implement the SerializableInterface');
            }

            $parser->writeBytes($object->getSize(), $object);
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
        $hex  = str_pad($this->math->decHex((int)$int), $bytes*2, '0', STR_PAD_LEFT);
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
}
