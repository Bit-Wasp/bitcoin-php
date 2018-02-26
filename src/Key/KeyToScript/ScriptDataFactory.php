<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\KeyToScript;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;

abstract class ScriptDataFactory
{
    /**
     * @param KeyInterface $key
     * @return ScriptAndSignData
     */
    abstract public function convertKey(KeyInterface $key): ScriptAndSignData;

    /**
     * @return string
     */
    abstract public function getScriptType(): string;
}
