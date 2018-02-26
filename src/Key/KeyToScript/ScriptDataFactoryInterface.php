<?php

namespace BitWasp\Bitcoin\Key\KeyToScript;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\Key;

interface ScriptDataFactoryInterface
{
    /**
     * @param Key $key
     * @return ScriptAndSignData
     */
    public function convertKey(Key $key);

    /**
     * @return string
     */
    public function getScriptType();
}
