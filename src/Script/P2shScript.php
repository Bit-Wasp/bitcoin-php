<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Address\AddressFactory;

class P2shScript extends Script
{

    /**
     * @var ScriptInterface
     */
    private $outputScript;

    /**
     * P2shScript constructor.
     * @param ScriptInterface $script
     * @param Opcodes|null $opcodes
     */
    public function __construct(ScriptInterface $script, Opcodes $opcodes = null)
    {
        parent::__construct($script->getBuffer(), $opcodes);
        $this->outputScript = ScriptFactory::scriptPubKey()->payToScriptHash($script);
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
        return AddressFactory::fromScript($this);
    }
}
