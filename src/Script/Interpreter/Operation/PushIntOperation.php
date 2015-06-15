<?php

namespace BitWasp\Bitcoin\Script\Interpreter\Operation;

use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptStack;
use BitWasp\Buffertools\Buffer;

class PushIntOperation
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
     * @throws \Exception
     */
    public function op($opCode, ScriptStack $mainStack)
    {
        $opCodes = $this->opCodes;

        switch ($opCodes->getOp($opCode)) {
            case 'OP_1NEGATE':
            case 'OP_1':
            case 'OP_2':
            case 'OP_3':
            case 'OP_4':
            case 'OP_5':
            case 'OP_6':
            case 'OP_7':
            case 'OP_8':
            case 'OP_9':
            case 'OP_10':
            case 'OP_11':
            case 'OP_12':
            case 'OP_13':
            case 'OP_14':
            case 'OP_15':
            case 'OP_16':
                $num = $opCode - ($opCodes->getOpByName('OP_1') - 1);
                $mainStack->push(new Buffer(chr($num)));
                return;
            default:
                throw new \Exception('Opcode not found');
        }
    }
}
