<?php

namespace BitWasp\Bitcoin\Script\Interpreter\Operation;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptStack;

class HashOperation
{
    /**
     * @var Opcodes
     */
    private $opCodes;

    /**
     * @param Opcodes $opCodes
     */
    public function __construct(Opcodes $opCodes)
    {
        $this->opCodes = $opCodes;
    }

    /**
     * @param $opCode
     * @param ScriptStack $mainStack
     * @throws \BitWasp\Bitcoin\Exceptions\ScriptStackException
     * @throws \Exception
     */
    public function op($opCode, ScriptStack $mainStack)
    {
        if ($mainStack->size() < 1) {
            throw new \Exception('Invalid stack operation');
        }

        $opCodes = $this->opCodes;
        $opName = $opCodes->getOp($opCode);
        $buffer = $mainStack->top(-1);

        if ($opCodes->cmp($opCode, 'OP_RIPEMD160') >= 0 && $opCodes->cmp($opCode, 'OP_HASH256') <= 0) {
            if ($opName == 'OP_RIPEMD160') {
                $hash = Hash::ripemd160($buffer);
            } elseif ($opName == 'OP_SHA1') {
                $hash = Hash::sha1($buffer);
            } elseif ($opName == 'OP_SHA256') {
                $hash = Hash::sha256($buffer);
            } elseif ($opName == 'OP_HASH160') {
                $hash = Hash::sha256ripe160($buffer);
            } else { // is hash256
                $hash = Hash::sha256d($buffer);
            }
            $mainStack->pop();
            $mainStack->push($hash);
        } else {
            throw new \Exception('Opcode not found');
        }
    }
}
