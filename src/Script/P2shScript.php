<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Exceptions\P2shScriptException;

class P2shScript extends Script
{
    /**
     * @var \BitWasp\Buffertools\BufferInterface
     */
    protected $scriptHash;

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
     * @throws P2shScriptException
     */
    public function __construct(ScriptInterface $script, Opcodes $opcodes = null)
    {
        if ($script instanceof WitnessScript) {
            $script = $script->getOutputScript();
        } else if ($script instanceof self) {
            throw new P2shScriptException("Cannot nest P2SH scripts.");
        }

        parent::__construct($script->getBuffer(), $opcodes);

        $this->scriptHash = $script->getScriptHash();
        $this->outputScript = ScriptFactory::scriptPubKey()->p2sh($this->scriptHash);
        $this->address = new ScriptHashAddress($this->scriptHash);
    }

    /**
     * @throws P2shScriptException
     */
    public function getWitnessScriptHash()
    {
        throw new P2shScriptException("Cannot compute witness-script-hash for a P2shScript");
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
