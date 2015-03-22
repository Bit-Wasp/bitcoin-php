<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Crypto\Hash;

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
        if ($script instanceof Buffer) {
            $this->script = $script->serialize();
        }

        $this->opcodes = new Opcodes;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return new Buffer($this->script);
    }

    /**
     * @return \BitWasp\Bitcoin\Address\ScriptHashAddress
     */
    public function getAddress()
    {
        $address = AddressFactory::fromScript($this);
        return $address;
    }

    /**
     * @return ScriptParser
     */
    public function getScriptParser()
    {
        return new ScriptParser(Bitcoin::getMath(), $this);
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
     * Return a buffer containing the hash of this script.
     *
     * @return \BitWasp\Bitcoin\Buffer
     */
    public function getScriptHash()
    {
        $hex  = $this->getBuffer()->serialize('hex');
        $hash = Hash::sha256ripe160($hex, true);

        $buffer = new Buffer($hash);
        return $buffer;
    }

    /**
     * Add an opcode to the script
     *
     * @param string $name
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

        $this->script .= $parsed->getBuffer()->serialize();
        return $this;
    }

    /**
     * @return bool
     */
    public function isPushOnly()
    {
        $pushOnly = true;
        foreach ($this->getScriptParser()->parse() as $entity) {
            $pushOnly &= $entity instanceof Buffer;
        }
        return $pushOnly;
    }

    /**
     * Return a human readable representation of the Script Opcodes, and data being
     * pushed to the stack
     *
     * @return string
     */
    public function getAsm()
    {
        $result = array_map(
            function ($value) {
                return $value instanceof Buffer
                    ? $value->serialize('hex')
                    : $value;
            },
            $this->getScriptParser()->parse()
        );

        return implode(" ", $result);
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
