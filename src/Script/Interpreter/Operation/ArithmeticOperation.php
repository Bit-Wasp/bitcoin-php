<?php

namespace BitWasp\Bitcoin\Script\Interpreter\Operation;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptStack;
use BitWasp\Buffertools\Buffer;

class ArithmeticOperation
{
    /**
     * @var Opcodes
     */
    private $opCodes;

    /**
     * @var Math
     */
    private $math;

    /**
     * @var callable
     */
    private $castToBool;

    /**
     * @var Buffer
     */
    private $_bn0;

    /**
     * @var Buffer
     */
    private $_bn1;
    
    /**
     * @param Opcodes $opCodes
     * @param Math $math
     * @param callable $castToBool
     * @param Buffer $_bn0
     * @param Buffer $_bn1
     */
    public function __construct(Opcodes $opCodes, Math $math, callable $castToBool, Buffer $_bn0, Buffer $_bn1)
    {
        $this->opCodes = $opCodes;
        $this->math = $math;
        $this->castToBool = $castToBool;
        $this->_bn0 = $_bn0;
        $this->_bn1 = $_bn1;
    }

    /**
     * @param $opCode
     * @param ScriptStack $mainStack
     * @throws \BitWasp\Bitcoin\Exceptions\ScriptStackException
     * @throws \Exception
     */
    private function singleValueCases($opCode, ScriptStack $mainStack)
    {
        if ($mainStack->size() < 1) {
            throw new \Exception('Invalid stack operation 1ADD');
        }
        $num = $mainStack->top(-1)->getInt();
        $opCodes = $this->opCodes;
        $opName = $opCodes->getOp($opCode);

        $math = $this->math;

        if ($opName == 'OP_1ADD') { // cscriptnum
            $num = $math->add($num, '1');
        } elseif ($opName == 'OP_1SUB') {
            $num = $math->sub($num, '1');
        } elseif ($opName == 'OP_2MUL') {
            $num = $math->mul(2, $num);
        } elseif ($opName == 'OP_NEGATE') { // cscriptnum
            $num = $math->sub(0, $num);
        } elseif ($opName == 'OP_ABS') {
            if ($math->cmp($num, '0') < 0) {
                $num = $math->sub(0, $num);
            }
        } elseif ($opName == 'OP_NOT') { // cscriptnum
            $num = ($math->cmp($num, '0') == 0);
        } else {
            // is OP_0NOTEQUAL
            $num = ($math->cmp($num, '0') !== 0);
        }

        $mainStack->pop();

        $buffer = Buffer::hex($math->decHex($num));
        $mainStack->push($buffer);
    }

    /**
     * @param $opCode
     * @param ScriptStack $mainStack
     * @throws \BitWasp\Bitcoin\Exceptions\ScriptStackException
     * @throws \Exception
     */
    private function twoValueCases($opCode, ScriptStack $mainStack)
    {
        if ($mainStack->size() < 2) {
            throw new \Exception('Invalid stack operation (greater than)');
        }

        $num1 = $mainStack->top(-2)->getInt(); // cscriptnum
        $num2 = $mainStack->top(-1)->getInt();

        $opCodes = $this->opCodes;
        $opName = $opCodes->getOp($opCode);
        $math = $this->math;
        $castToBool = $this->castToBool;

        if ($opName == 'OP_ADD') {
            $num = $math->add($num1, $num2);
        } elseif ($opName == 'OP_SUB') { // cscriptnum
            $num = $math->sub($num1, $num2);
        } elseif ($opName == 'OP_BOOLAND') { // cscriptnum
            $num = $math->cmp($num1, $this->_bn0->getInt()) !== 0 && $math->cmp($num2, $this->_bn0->getInt()) !== 0;
        } elseif ($opName == 'OP_BOOLOR') {
            $num = $math->cmp($num1, $this->_bn0->getInt()) !== 0 || $math->cmp($num2, $this->_bn0->getInt()) !== 0;
        } elseif ($opName == 'OP_NUMEQUAL') { // cscriptnum
            $num = $math->cmp($num1, $num2) == 0;
        } elseif ($opName == 'OP_NUMEQUALVERIFY') { // cscriptnum
            $num = $math->cmp($num1, $num2) == 0;
        } elseif ($opName == 'OP_NUMNOTEQUAL') {
            $num = $math->cmp($num1, $num2) !== 0;
        } elseif ($opName == 'OP_LESSTHAN') { // cscriptnum
            $num = $math->cmp($num1, $num2) < 0;
        } elseif ($opName == 'OP_GREATERTHAN') {
            $num = $math->cmp($num1, $num2) > 0;
        } elseif ($opName == 'OP_LESSTHANOREQUAL') { // cscriptnum
            $num = $math->cmp($num1, $num2) <= 0;
        } elseif ($opName == 'OP_GREATERTHANOREQUAL') {
            $num = $math->cmp($num1, $num2) >= 0;
        } elseif ($opName == 'OP_MIN') {
            $num = ($math->cmp($num1, $num2) <= 0) ? $num1 : $num2;
        } else {
            // is OP_MAX
            $num = ($math->cmp($num1, $num2) >= 0) ? $num1 : $num2;
        }

        $mainStack->pop();
        $mainStack->pop();
        $buffer = Buffer::hex($math->decHex($num));
        $mainStack->push($buffer);

        if ($opCodes->isOp($opCode, 'OP_NUMEQUALVERIFY')) {
            if ($castToBool($mainStack->top(-1))) {
                $mainStack->pop();
            } else {
                throw new \Exception('NUM EQUAL VERIFY error');
            }
        }
    }

    /**
     * @param $opCode
     * @param ScriptStack $mainStack
     * @throws \BitWasp\Bitcoin\Exceptions\ScriptStackException
     * @throws \Exception
     */
    private function threeValueCases($opCode, ScriptStack $mainStack)
    {
        $opName = $this->opCodes->getOp($opCode);
        $math = $this->math;

        if ($opName == 'OP_WITHIN') { //cscriptnum
            if ($mainStack->size() < 3) {
                throw new \Exception('Invalid stack operation');
            }
            $num1 = $mainStack->top(-3)->getInt();
            $num2 = $mainStack->top(-2)->getInt();
            $num3 = $mainStack->top(-1)->getInt();

            $value = $math->cmp($num2, $num1) <= 0 && $math->cmp($num1, $num3) < 0;
            $mainStack->pop();
            $mainStack->pop();
            $mainStack->pop();
            $mainStack->push($value ? $this->_bn1 : $this->_bn0);
            return;
        }

        throw new \Exception('Opcode not found');
    }

    /**
     * @param $opCode
     * @param ScriptStack $mainStack
     * @throws \BitWasp\Bitcoin\Exceptions\ScriptStackException
     * @throws \Exception
     */
    public function op($opCode, ScriptStack $mainStack)
    {
        if ($this->opCodes->cmp($opCode, 'OP_1ADD') >= 0 && $this->opCodes->cmp($opCode, 'OP_0NOTEQUAL') <= 0) {
            $this->singleValueCases($opCode, $mainStack);
        } else if ($this->opCodes->isOp($opCode, 'OP_ADD')
            || $this->opCodes->isOp($opCode, 'OP_SUB')
            || ($this->opCodes->cmp($opCode, 'OP_BOOLAND') >= 0 && $this->opCodes->cmp($opCode, 'OP_MAX') <= 0)
        ) {
            $this->twoValueCases($opCode, $mainStack);
        } else {
            $this->threeValueCases($opCode, $mainStack);
        }
    }
}
