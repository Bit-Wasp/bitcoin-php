<?php

namespace BitWasp\Bitcoin\Key\KeyToScript;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;

interface ScriptDataFactoryInterface
{
    /**
     * @param KeyInterface $key
     * @return ScriptAndSignData
     */
    public function convertKey(KeyInterface $key);

    /**
     * @return string
     */
    public function getScriptType();
}
