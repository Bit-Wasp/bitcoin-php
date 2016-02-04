<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Script\Parser\Parser;
use BitWasp\Bitcoin\SerializableInterface;

interface ScriptInterface extends SerializableInterface
{
    /**
     * @return \BitWasp\Buffertools\BufferInterface
     */
    public function getScriptHash();

    /**
     * @return Parser
     */
    public function getScriptParser();

    /**
     * @return Opcodes
     */
    public function getOpcodes();

    /**
     * @return bool
     */
    public function isPushOnly();

    /**
     * @param WitnessProgram $witness
     * @return bool
     */
    public function isWitness(WitnessProgram & $witness);

    /**
     * @param bool $accurate
     * @return int
     */
    public function countSigOps($accurate = true);

    /**
     * @param ScriptInterface $scriptSig
     * @return int
     */
    public function countP2shSigOps(ScriptInterface $scriptSig);
}
