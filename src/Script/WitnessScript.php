<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Address\SegwitAddress;
use BitWasp\Bitcoin\Exceptions\WitnessScriptException;

class WitnessScript extends Script
{

    /**
     * @var ScriptInterface
     */
    private $outputScript;

    /**
     * @var \BitWasp\Buffertools\BufferInterface
     */
    protected $witnessScriptHash;

    /**
     * @var WitnessProgram|null
     */
    private $witnessProgram;

    /**
     * @var SegwitAddress
     */
    private $address;

    /**
     * WitnessScript constructor.
     * @param ScriptInterface $script
     * @param Opcodes|null $opcodes
     * @throws WitnessScriptException
     */
    public function __construct(ScriptInterface $script, Opcodes $opcodes = null)
    {
        if ($script instanceof self) {
            throw new WitnessScriptException("Cannot nest V0 P2WSH scripts.");
        } else if ($script instanceof P2shScript) {
            throw new WitnessScriptException("Cannot embed a P2SH script in a V0 P2WSH script.");
        }

        parent::__construct($script->getBuffer(), $opcodes);

        $this->witnessScriptHash = $script->getWitnessScriptHash();
        $this->outputScript = ScriptFactory::scriptPubKey()->p2wsh($this->witnessScriptHash);
    }

    /**
     * @return WitnessProgram
     */
    public function getWitnessProgram(): WitnessProgram
    {
        if (null === $this->witnessProgram) {
            $this->witnessProgram = WitnessProgram::v0($this->witnessScriptHash);
        }

        return $this->witnessProgram;
    }

    /**
     * @return SegwitAddress
     */
    public function getAddress(): SegwitAddress
    {
        if (null === $this->address) {
            $this->address = new SegwitAddress($this->getWitnessProgram());
        }

        return $this->address;
    }

    /**
     * @return ScriptInterface
     */
    public function getOutputScript(): ScriptInterface
    {
        return $this->getWitnessProgram()->getScript();
    }
}
