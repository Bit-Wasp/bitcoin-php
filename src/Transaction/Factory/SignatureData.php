<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\ScriptClassifierInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Buffertools\BufferInterface;

class SignatureData
{
    public $scriptType = null;
    public $innerScriptType = null;

    /**
     * @var BufferInterface[]
     */
    public $signatures = null;

    /**
     * @var ScriptWitnessInterface[]
     */
    public $witnesses = [];

    /**
     * @var PublicKeyInterface[]
     */
    public $publicKeys = [];

    /**
     * @var BufferInterface|BufferInterface[]
     */
    public $solution;

    /**
     * @var ScriptInterface
     */
    public $p2shScript;

    /**
     * @var ScriptInterface
     */
    public $witnessScript;
}
