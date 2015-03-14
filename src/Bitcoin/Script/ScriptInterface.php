<?php

namespace Afk11\Bitcoin\Script;

use Afk11\Bitcoin\SerializableInterface;

interface ScriptInterface extends SerializableInterface
{
    /**
     * @return mixed
     */
    public function getScriptHash();

    /**
     * @return array
     */
    public function parse();

    /**
     * @return Opcodes
     */
    public function getOpcodes();

    /**
     * @return mixed
     */
    public function isPushOnly();
}
