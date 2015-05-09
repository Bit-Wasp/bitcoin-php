<?php

namespace BitWasp\Bitcoin\Exceptions\ScriptRuntime;

use BitWasp\Bitcoin\Exceptions\ScriptRuntimeException;
use BitWasp\Bitcoin\Script\ScriptInterpreterFlags;

class VerifyP2sh extends ScriptRuntimeException
{
    public function getFlag()
    {
        return ScriptInterpreterFlags::VERIFY_P2SH;
    }
}
