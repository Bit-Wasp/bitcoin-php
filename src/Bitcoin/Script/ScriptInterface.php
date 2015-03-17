<?php

namespace Afk11\Bitcoin\Script;

use Afk11\Bitcoin\SerializableInterface;
use Afk11\Bitcoin\Address\Address;

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
