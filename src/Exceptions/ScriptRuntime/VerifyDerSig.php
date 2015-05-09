<?php

namespace BitWasp\Bitcoin\Exceptions\ScriptRuntime;

use BitWasp\Bitcoin\Exceptions\ScriptRuntimeException;
use BitWasp\Bitcoin\Script\ScriptInterpreterFlags;

class VerifyDerSig extends ScriptRuntimeException
{
    public function getFlag()
    {
        return ScriptInterpreterFlags::VERIFY_DERSIG;
    }
}
