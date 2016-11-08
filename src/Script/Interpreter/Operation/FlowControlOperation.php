<?php

namespace BitWasp\Bitcoin\Script\Interpreter\Operation;

use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptStack;

class FlowControlOperation
{
    /**
     * @var Opcodes
     */
    private $opCodes;

    /**
     * @var callable
     */
    private $castToBool;

    /**
     * @param Opcodes $opCodes
     * @param callable $castToBool
     */
    public function __construct(Opcodes $opCodes, callable $castToBool)
    {
        $this->opCodes = $opCodes;
        $this->castToBool = $castToBool;
    }

    /**
     * @param $opCode
     * @param ScriptStack $mainStack
     * @param ScriptStack $vfStack
     * @param $fExec
     * @throws \BitWasp\Bitcoin\Exceptions\ScriptStackException
     * @throws \Exception
     */
    public function op($opCode, ScriptStack $mainStack, ScriptStack $vfStack, $fExec)
    {
        $opCodes = $this->opCodes;
        $castToBool = $this->castToBool;

        $opName = $opCodes->getOp($opCode);
        if ($opName == 'OP_NOP') {
            return;
        } elseif (in_array($opName, ['OP_IF', 'OP_NOTIF'])) { // cscriptnum
            // <expression> if [statements] [else [statements]] endif
            $value = false;
            if ($fExec) {
                if ($mainStack->size() < 1) {
                    throw new \Exception('Unbalanced conditional');
                }
                // todo
                $buffer = $mainStack->pop();
                $value = $castToBool($buffer);
                if ($opCodes->isOp($opCode, 'OP_NOTIF')) {
                    $value = !$value;
                }
            }
            $vfStack->push($value);
            return;
        } else if ($opName == 'OP_ELSE') {
            if ($vfStack->size() == 0) {
                throw new \Exception('Unbalanced conditional');
            }
            $vfStack->set($vfStack->end() - 1, !$vfStack->end());
            return;
        } else if ($opName == 'OP_ENDIF') {
            if ($vfStack->size() == 0) {
                throw new \Exception('Unbalanced conditional');
            }
            // todo
            return;
        } else if ($opName == 'OP_VERIFY') {
            if ($mainStack->size() < 1) {
                throw new \Exception('Invalid stack operation');
            }
            $value = $castToBool($mainStack->top(-1));
            if (!$value) {
                throw new \Exception('Error: verify');
            }
            $mainStack->pop();
            return;
        } else if ($opName == 'OP_RETURN') {
            throw new \Exception('Error: OP_RETURN');
        }

        throw new \Exception('Opcode not found');
    }
}
