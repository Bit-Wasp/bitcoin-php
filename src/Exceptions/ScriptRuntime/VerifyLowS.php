<?php

namespace BitWasp\Bitcoin\Exceptions\ScriptRuntime;

use BitWasp\Bitcoin\Exceptions\ScriptRuntimeException;
use BitWasp\Bitcoin\Script\ScriptInterpreterFlags;

class VerifyLowS extends ScriptRuntimeException
{
    public function getFlag()
    {
        return ScriptInterpreterFlags::VERIFY_LOW_S;
    }
}
