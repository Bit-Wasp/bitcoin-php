<?php

namespace Afk11\Bitcoin\Script;

class ScriptInterpreterFlags
{
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
        // Set up current limits
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
