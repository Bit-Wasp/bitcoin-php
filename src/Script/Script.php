<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\Parser\Parser;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializable;

class Script extends Serializable implements ScriptInterface
{

    /**
     * @var Opcodes
     */
    private $opCodes;

    /**
     * @var null|string
     */
    protected $script;

    /**
     * @param Buffer|null $script
     * @param Opcodes|null $opCodes
     */
    public function __construct(Buffer $script = null, Opcodes $opCodes = null)
    {
        $this->script = $script instanceof Buffer ? $script->getBinary() : '';
        $this->opCodes = $opCodes ?: new Opcodes();
    }

    /**
     * @return Buffer
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
     * @return \BitWasp\Buffertools\Buffer
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

        if (!$data instanceof Buffer) {
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
        foreach ($this->getScriptParser()->parse() as $entity) {
            $pushOnly &= $entity instanceof Buffer;
        }
        return $pushOnly;
    }
}
