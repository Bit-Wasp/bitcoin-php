<?php

namespace BitWasp\Bitcoin\Script;

class ScriptInterpreterFlags
{

    const SCRIPT_VERIFY_NONE = 0;
    // 1 << 0
    const SCRIPT_VERIFY_P2SH = 1;

    // 1 << 1
    const SCRIPT_VERIFY_STRICTENC = 2;

    // 1 < 2
    const SCRIPT_VERIFY_DERSIG = 4;

    const SCRIPT_VERIFY_LOW_S = 8;

    const SCRIPT_VERIFY_NULL_DUMMY = 16;
    const SCRIPT_VERIFY_SIGPUSHONLY = 32;
    const SCRIPT_VERIFY_MINIMALDATA = 64;
    const SCRIPT_VERIFY_DISCOURAGE_UPGARDABLE_NOPS = 128;

    public $discourageUpgradableNOPS = false;
    public $maxBytes = 10000;
    public $maxElementSize = 520;
    public $checkDisabledOpcodes = false;
    public $verifyMinimalPushdata = false;
    public $verifyDERSignatures = false;
    public $verifyStrictEncoding = false;
    public $verifyP2SH = false;

    public function __construct()
    {

    }

    public static function defaults()
    {
        $flags = new self();
        // Set up present settings
        $flags->discourageUpgradableNOPS = true;
        $flags->maxBytes                 = 10000;
        $flags->maxElementSize           = 520;
        $flags->checkDisabledOpcodes     = true;
        $flags->verifyMinimalPushdata    = true;
        $flags->verifyDERSignatures      = true;
        $flags->verifyStrictEncoding     = true;
        $flags->verifyP2SH               = true;
        return $flags;
    }
}
