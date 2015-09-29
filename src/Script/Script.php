<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
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
    protected $script;

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

    /**
     * @param ScriptInterface $script
     * @return $this
     */
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
        return AddressFactory::fromScript($this);
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
        return Hash::sha256ripe160($this->getBuffer());
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
