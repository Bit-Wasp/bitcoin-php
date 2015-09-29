<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\SerializableInterface;
use BitWasp\Bitcoin\Address\Address;

interface ScriptInterface extends SerializableInterface
{
    /**
     * @return \BitWasp\Buffertools\Buffer
     */
    public function getScriptHash();

    /**
     * @return Address
     */
    public function getAddress();

    /**
     * @return ScriptParser
     */
    public function getScriptParser();

    /**
     * @return Opcodes
     */
    public function getOpcodes();

    /**
     * @return bool
     */
    public function isPushOnly();
}
