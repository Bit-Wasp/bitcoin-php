<?php

namespace BitWasp\Bitcoin\Script;

class ScriptInterpreterFlags
{

    const VERIFY_NONE = 0;
    // 1 << 0
    const VERIFY_P2SH = 1;

    // 1 << 1
    const VERIFY_STRICTENC = 2;

    // 1 < 2
    const VERIFY_DERSIG = 4;

    const VERIFY_LOW_S = 8;

    const VERIFY_NULL_DUMMY = 16;
    const VERIFY_SIGPUSHONLY = 32;
    const VERIFY_MINIMALDATA = 64;
    const VERIFY_DISCOURAGE_UPGARDABLE_NOPS = 128;

    // consensus derived
    public $verifyP2SH = false;
    public $verifyStrictEncoding = false;
    public $verifyDERSignatures = false;
    public $verifyLowS = false;
    public $verifySigPushonly = false;
    public $verifyMinimalPushdata = false;
    public $discourageUpgradableNOPS = false;

    // globals
    public $maxBytes = 10000;
    public $maxElementSize = 520;
    public $checkDisabledOpcodes = false;

    public function __construct()
    {

    }

    public static function defaults()
    {
        $flags = new self();
        // Set up present settings
        $flags->discourageUpgradableNOPS = true;
        $flags->checkDisabledOpcodes     = true;
        $flags->verifyLowS               = true;
        $flags->verifySigPushonly        = true;
        $flags->verifyMinimalPushdata    = true;
        $flags->verifyDERSignatures      = true;
        $flags->verifyStrictEncoding     = true;
        $flags->verifyP2SH               = true;
        return $flags;
    }
}
