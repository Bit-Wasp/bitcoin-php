<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Address\ScriptHashAddress;

class P2shScript extends Script
{

    /**
     * @var ScriptHashAddress
     */
    private $address;

    /**
     * @var ScriptInterface
     */
    private $outputScript;

    /**
     * @var \BitWasp\Buffertools\BufferInterface
     */
    private $scriptHash;

    /**
     * P2shScript constructor.
     * @param ScriptInterface $script
     * @param Opcodes|null $opcodes
     */
    public function __construct(ScriptInterface $script, Opcodes $opcodes = null)
    {
        parent::__construct($script->getBuffer(), $opcodes);
        $this->scriptHash = $script->getScriptHash();
        $this->outputScript = ScriptFactory::scriptPubKey()->p2sh($this->scriptHash);
        $this->address = new ScriptHashAddress($this->scriptHash);
    }

    /**
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function getScriptHash()
    {
        return $this->scriptHash;
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
