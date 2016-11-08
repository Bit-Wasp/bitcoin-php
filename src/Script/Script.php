<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializable;

class Script extends Serializable implements ScriptInterface
{

    /**
     * @var Opcodes
     */
    private $opcodes;

    /**
     * @var null|string
     */
    private $script;

    /**
     * Initialize container
     *
     * @param Buffer $script
     */
    public function __construct(Buffer $script = null)
    {
        $this->script = $script instanceof Buffer ? $script->getBinary() : '';
        $this->opcodes = new Opcodes;
    }

    public function concat(ScriptInterface $script)
    {
        $this->script .= $script->getBinary();
        return $this;
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
     * @return \BitWasp\Buffertools\Buffer
     */
    public function getScriptHash()
    {
        $hex = $this->getBuffer();
        $hash = Hash::sha256ripe160($hex);

        return $hash;
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
    public function push(Buffer $data)
    {
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

        $this->script .= $parsed->getBuffer()->getBinary();
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
}
