<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\SerializableInterface;
use BitWasp\Bitcoin\Address\Address;

interface ScriptInterface extends SerializableInterface
{
    /**
     * @return mixed
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
     * @return mixed
     */
    public function isPushOnly();
}
