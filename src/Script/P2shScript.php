<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Address\ScriptHashAddress;

class P2shScript extends Script
{

    /**
     * @var ScriptInterface
     */
    private $outputScript;

    /**
     * @var ScriptHashAddress
     */
    private $address;

    /**
     * P2shScript constructor.
     * @param ScriptInterface $script
     * @param Opcodes|null $opcodes
     */
    public function __construct(ScriptInterface $script, Opcodes $opcodes = null)
    {
        parent::__construct($script->getBuffer(), $opcodes);
        $hash = $script->getScriptHash();
        $this->outputScript = ScriptFactory::scriptPubKey()->p2sh($hash);
        $this->address = new ScriptHashAddress($hash);
    }

    /**
     * @return ScriptInterface
     */
    public function getOutputScript()
    {
        return $this->outputScript;
    }

    /**
     * @return \BitWasp\Bitcoin\Address\ScriptHashAddress
     */
    public function getAddress()
    {
        return $this->address;
    }
}
