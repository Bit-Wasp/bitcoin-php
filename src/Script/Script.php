<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Bitcoin\Script\Parser\Parser;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Buffertools\BufferInterface;

class Script extends Serializable implements ScriptInterface
{

    /**
     * @var Opcodes
     */
    protected $opCodes;

    /**
     * @var string
     */
    protected $script;

    /**
     * @param BufferInterface $script
     * @param Opcodes|null $opCodes
     */
    public function __construct(BufferInterface $script = null, Opcodes $opCodes = null)
    {
        $this->script = $script instanceof BufferInterface ? $script->getBinary() : '';
        $this->opCodes = $opCodes ?: new Opcodes();
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer()
    {
        return new Buffer($this->script);
    }

    /**
     * @return Parser
     */
    public function getScriptParser()
    {
        return new Parser(Bitcoin::getMath(), $this);
    }

    /**
     * Get all opcodes
     *
     * @return Opcodes
     */
    public function getOpCodes()
    {
        return $this->opCodes;
    }

    /**
     * Return a buffer containing the hash of this script.
     *
     * @return BufferInterface
     */
    public function getScriptHash()
    {
        return Hash::sha256ripe160($this->getBuffer());
    }

    /**
     * @param bool|true $accurate
     * @return int
     */
    public function countSigOps($accurate = true)
    {
        $count = 0;
        $parser = $this->getScriptParser();

        $lastOp = 0xff;
        foreach ($parser as $exec) {
            if ($exec->isPush()) {
                continue;
            }

            $op = $exec->getOp();
            if ($op > Opcodes::OP_PUSHDATA4) {
                // None of these are pushdatas, so just an opcode
                if ($op === Opcodes::OP_CHECKSIG || $op === Opcodes::OP_CHECKSIGVERIFY) {
                    $count++;
                } elseif ($op === Opcodes::OP_CHECKMULTISIG || $op === Opcodes::OP_CHECKMULTISIGVERIFY) {
                    if ($accurate && ($lastOp >= Opcodes::OP_1 && $lastOp <= Opcodes::OP_16)) {
                        $c = ($lastOp - (Opcodes::OP_1 - 1));
                        $count += $c;
                    } else {
                        $count += 20;
                    }
                }

                $lastOp = $op;
            }
        }

        return $count;
    }

    /**
     * @param WitnessProgram $program
     * @return int
     */
    private function witnessSigOps(WitnessProgram $program)
    {
        if ($program->getVersion() == 0) {
            return $program->getOutputScript()->countSigOps(true);
        }

        return 0;
    }

    /**
     * @param ScriptInterface $scriptSig
     * @param ScriptWitnessInterface $scriptWitness
     * @param int $flags
     * @return int
     */
    public function countWitnessSigOps(ScriptInterface $scriptSig, ScriptWitnessInterface $scriptWitness, $flags)
    {
        if ($flags & InterpreterInterface::VERIFY_WITNESS === 0) {
            return 0;
        }

        $program = null;
        if ($this->isWitness($program)) {
            return $this->witnessSigOps($program);
        }

        if ((new OutputClassifier($this))->isPayToScriptHash()) {
            $parsed = $scriptSig->getScriptParser()->decode();
            $subscript = new Script(end($parsed)->getData());
            if ($subscript->isWitness($program)) {
                return $this->witnessSigOps($program);
            }
        }

        return 0;
    }

    /**
     * @param ScriptInterface $scriptSig
     * @return int
     */
    public function countP2shSigOps(ScriptInterface $scriptSig)
    {
        if (ScriptFactory::scriptPubKey()
            ->classify($this)
            ->isPayToScriptHash() === false
        ) {
            return $this->countSigOps(true);
        }

        $parser = $scriptSig->getScriptParser();

        $data = null;
        foreach ($parser as $exec) {
            if ($exec->getOp() > Opcodes::OP_16) {
                return 0;
            }

            if ($exec->isPush()) {
                $data = $exec->getData();
            }
        }

        if (!$data instanceof BufferInterface) {
            return 0;
        }

        return (new Script($data))->countSigOps(true);
    }

    /**
     * @return bool
     */
    public function isPushOnly()
    {
        $pushOnly = true;
        foreach ($this->getScriptParser()->decode() as $entity) {
            $pushOnly &= $entity->isPush();
        }
        return $pushOnly;
    }

    /**
     * @param WitnessProgram|null $program
     * @return bool
     */
    public function isWitness(WitnessProgram & $program = null)
    {
        $buffer = $this->getBuffer();
        $size = $buffer->getSize();
        if ($size < 4 || $size > 34) {
            return false;
        }

        $parser = $this->getScriptParser();
        $script = $parser->decode();
        if (!$script[1]->isPush()) {
            return false;
        }

        $version = $script[0]->getOp();
        if ($version != Opcodes::OP_0 && ($version < Opcodes::OP_1 || $version > Opcodes::OP_16)) {
            return false;
        }

        $witness = $script[1];
        if ($size === $witness->getDataSize() + 2) {
            $program = new WitnessProgram(decodeOpN($version), $witness->getData());
            return true;
        }

        return false;
    }
}
