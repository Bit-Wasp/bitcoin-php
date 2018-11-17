<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\KeyToScript;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;

abstract class ScriptDataFactory
{
    /**
     * @param KeyInterface ...$keys
     * @return ScriptAndSignData
     */
    abstract public function convertKey(KeyInterface ...$keys): ScriptAndSignData;

    /**
     * @return string
     */
    abstract public function getScriptType(): string;
}
