<?php

namespace BitWasp\Bitcoin\Key\KeyToScript;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;

abstract class ScriptDataFactory
{
    /**
     * @param KeyInterface $key
     * @return ScriptAndSignData
     */
    abstract public function convertKey(KeyInterface $key);

    /**
     * @return string
     */
    abstract public function getScriptType();
}
