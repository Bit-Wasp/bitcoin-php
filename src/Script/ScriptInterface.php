<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Script\Parser\Parser;
use BitWasp\Bitcoin\SerializableInterface;
use BitWasp\Buffertools\BufferInterface;

interface ScriptInterface extends SerializableInterface
{
    /**
     * @return BufferInterface
     */
    public function getScriptHash(): BufferInterface;

    /**
     * @return BufferInterface
     */
    public function getWitnessScriptHash(): BufferInterface;

    /**
     * @return Parser
     */
    public function getScriptParser(): Parser;

    /**
     * @return Opcodes
     */
    public function getOpcodes(): Opcodes;

    /**
     * Returns boolean indicating whether script
     * was push only. If true, $ops is populated
     * with the contained buffers
     * @param array $ops
     * @return bool
     */
    public function isPushOnly(array &$ops = null): bool;

    /**
     * @param WitnessProgram|null $witness
     * @return bool
     */
    public function isWitness(& $witness): bool;

    /**
     * @param BufferInterface $scriptHash
     * @return bool
     */
    public function isP2SH(& $scriptHash): bool;

    /**
     * @param bool $accurate
     * @return int
     */
    public function countSigOps(bool $accurate = true): int;

    /**
     * @param ScriptInterface $scriptSig
     * @return int
     */
    public function countP2shSigOps(ScriptInterface $scriptSig): int;

    /**
     * @param ScriptInterface $scriptSig
     * @param ScriptWitnessInterface $witness
     * @param int $flags
     * @return int
     */
    public function countWitnessSigOps(ScriptInterface $scriptSig, ScriptWitnessInterface $witness, int $flags): int;

    /**
     * @param ScriptInterface $script
     * @return bool
     */
    public function equals(ScriptInterface $script): bool;
}
