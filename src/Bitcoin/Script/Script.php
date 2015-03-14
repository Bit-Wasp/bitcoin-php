<?php

namespace Afk11\Bitcoin\Script;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Network\NetworkInterface;
use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Address\AddressFactory;
use Afk11\Bitcoin\Crypto\Hash;

class Script implements ScriptInterface
{

    /**
     * @var Opcodes
     */
    public $opcodes;

    /**
     * @var null|string
     */
    protected $script = null;

    /**
     * Initialize container
     *
     * @param Buffer $script
     */
    public function __construct(Buffer $script = null)
    {
        $this->set($script);
        $this->opcodes = new Opcodes;
        return $this;
    }

    /**
     * Get all opcodes (OP_X => opcode)
     *
     * @return Opcodes
     */
    public function getOpCodes()
    {
        return $this->opcodes;
    }

    /**
     * Add an opcode to the script
     *
     * @param $name
     * @return $this
     */
    public function op($name)
    {
        $code = $this->opcodes->getOpByName($name);
        $this->script .= pack("H*", (Bitcoin::getMath()->decHex($code)));
        return $this;
    }

    /**
     * Push data into the stack.
     *
     * @param $data
     * @return $this
     * @throws \Exception
     */
    public function push($data)
    {
        if (!$data instanceof Buffer) {
            $data = Buffer::hex($data);
        }

        $length = $data->getSize();
        $parsed = new Parser();

        /** Note that larger integers are serialized without flipping bits - Big endian */

        if ($length < $this->opcodes->getOpByName('OP_PUSHDATA1')) {
            $parsed = $parsed->writeWithLength($data);

        } elseif ($length <= 0xff) {
            $parsed->writeInt(1, $this->opcodes->getOpByName('OP_PUSHDATA1'))
                ->writeInt(1, $length, false)
                ->writeBytes($length, $data);

        } elseif ($length <= 0xffff) {
            $parsed->writeInt(1, $this->opcodes->getOpByName('OP_PUSHDATA2'))
                ->writeInt(2, $length, true)
                ->writeBytes($length, $data);

        } else {
            $parsed->writeInt(1, $this->opcodes->getOpByName('OP_PUSHDATA4'))
                ->writeInt(4, $length, true)
                ->writeBytes($length, $data);
        }

        $this->script .=  $parsed->getBuffer()->serialize();
        return $this;
    }

    /**
     * Parse a script into opcodes and Buffers of data
     *
     * @return array
     */
    public function parse()
    {
        $data = array();

        // Load script as a byte string
        $script = $this->getBuffer()->serialize('hex');
        $parser = new Parser($script);

        while ($op = $parser->readBytes(1)) {
            $opCode = $op->serialize('int');

            if ($opCode < 1) {
                // False, or OP_0
                $push = Buffer::hex('00');

            } elseif ($opCode < 75) {
                // When < 75, this opCode is the length of the following string
                $push = $parser->readBytes($opCode);

            } elseif ($opCode <= 78) {
                // Each pushdata opcode is followed by the length of the string.
                // The number of bytes which encode the length change with the opcode.
                if ($opCode == $this->opcodes->getOpByName('OP_PUSHDATA1')) {
                    $lengthOfLen = 1;
                } elseif ($opCode == $this->opcodes->getOpByName('OP_PUSHDATA2')) {
                    $lengthOfLen = 2;
                } else {
                    // ($opCode == $this->opcodes->getOpCode('OP_PUSHDATA4')) {
                    $lengthOfLen = 4;
                }

                $length = $parser->readBytes($lengthOfLen, true)->serialize('int');
                $push   = $parser->readBytes($length);

            } else {
                // None of these pushdatas, so just an opcode
                $push = $this->opcodes->getOp($opCode);
            }

            $data[] = $push;

        }
        return $data;

    }

    /**
     * When given a Buffer or hex string, set the script to be this.
     *
     * @param $scriptData
     * @return $this
     */
    public function set($scriptData)
    {
        if ($scriptData instanceof Buffer) {
            $this->script = $scriptData->serialize();
        } else {
            $this->script = pack("H*", $scriptData);
        }
        return $this;
    }

    /**
     * Return a human readable representation of the Script Opcodes, and data being
     * pushed to the stack
     *
     * @return string
     */
    public function getAsm()
    {
        $result = array();
        $parse  = $this->parse();

        foreach ($parse as $item) {
            if ($item instanceof Buffer) {
                // Buffer
                $result[] = $item->serialize('hex');
            } else {
                // Opcode
                $result[] = $item;

            }
        }

        return implode(" ", $result);
    }

    /**
     * Return a buffer containing the hash of this script.
     *
     * @return \Afk11\Bitcoin\Buffer
     */
    public function getScriptHash()
    {
        $hex  = $this->getBuffer()->serialize('hex');
        $hash = Hash::sha256ripe160($hex, true);

        $buffer = new Buffer($hash);
        return $buffer;
    }

    /**
     * Return a varInt, based on the size of the script.
     *
     * @return string
     * @throws \Exception
     */
    public function getVarInt()
    {
        $size   = $this->getBuffer()->getSize();
        $varInt = Parser::numToVarInt($size);
        return $varInt;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return new Buffer($this->script);
    }

    /**
     * @param NetworkInterface $network
     * @return \Afk11\Bitcoin\Address\ScriptHashAddress
     */
    public function getAddress(NetworkInterface $network)
    {
        $address = AddressFactory::fromScript($network, $this);
        return $address;
    }

    /**
     * Return the object as an array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'hex' => $this->getBuffer()->serialize('hex'),
            'asm' => $this->getAsm()
        );
    }
}
