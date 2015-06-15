<?php

namespace BitWasp\Bitcoin\Script\Interpreter\Operation;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptStack;
use BitWasp\Buffertools\Buffer;

class StackOperation
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
     * @param Math $math
     * @param callable $castToBool
     */
    public function __construct(Opcodes $opCodes, Math $math, callable $castToBool)
    {
        $this->opCodes = $opCodes;
        $this->math = $math;
        $this->castToBool = $castToBool;
    }

    /**
     * @param $opCode
     * @param ScriptStack $mainStack
     * @param ScriptStack $altStack
     * @throws \BitWasp\Bitcoin\Exceptions\ScriptStackException
     * @throws \Exception
     */
    public function op($opCode, ScriptStack $mainStack, ScriptStack $altStack)
    {
        $opCodes = $this->opCodes;
        $opName = $this->opCodes->getOp($opCode);
        $castToBool = $this->castToBool;

        if ($opName == 'OP_TOALTSTACK') {
            if ($mainStack->size() < 1) {
                throw new \Exception('Invalid stack operation OP_TOALTSTACK');
            }
            $altStack->push($mainStack->pop());
            return;
        } else if ($opName == 'OP_FROMALTSTACK') {
            if ($altStack->size() < 1) {
                throw new \Exception('Invalid alt-stack operation OP_FROMALTSTACK');
            }
            $mainStack->push($altStack->pop());
            return;
        } else if ($opName == 'OP_IFDUP') {
            // If top value not zero, duplicate it.
            if ($mainStack->size() < 1) {
                throw new \Exception('Invalid stack operation OP_IFDUP');
            }
            $vch = $mainStack->top(-1);
            if ($castToBool($vch)) {
                $mainStack->push($vch);
            }
            return;
        } else if ($opName == 'OP_DEPTH') {
            $num = $mainStack->size();
            $bin = Buffer::hex($this->math->decHex($num));
            $mainStack->push($bin);
            return;
        } else if ($opName == 'OP_DROP') {
            if ($mainStack->size() < 1) {
                throw new \Exception('Invalid stack operation OP_DROP');
            }
            $mainStack->pop();
            return;
        } else if ($opName == 'OP_DUP') {
            if ($mainStack->size() < 1) {
                throw new \Exception('Invalid stack operation OP_DUP');
            }
            $vch = $mainStack->top(-1);
            $mainStack->push($vch);
            return;
        } else if ($opName == 'OP_NIP') {
            if ($mainStack->size() < 2) {
                throw new \Exception('Invalid stack operation OP_NIP');
            }
            $mainStack->erase(-2);
            return;
        } else if ($opName == 'OP_OVER') {
            if ($mainStack->size() < 2) {
                throw new \Exception('Invalid stack operation OP_OVER');
            }
            $vch = $mainStack->top(-2);
            $mainStack->push($vch);
            return;
        } else if (in_array($opName, ['OP_PICK', 'OP_ROLL'])) { // cscriptnum
            if ($mainStack->size() < 2) {
                throw new \Exception('Invalid stack operation OP_PICK');
            }
            $n = $mainStack->top(-1)->getInt();
            $mainStack->pop();
            if ($this->math->cmp($n, 0) < 0 || $this->math->cmp($n, $mainStack->size()) >= 0) {
                throw new \Exception('Invalid stack operation OP_PICK');
            }

            $pos = $this->math->sub($this->math->sub(0, $n), 1);
            $vch = $mainStack->top($pos);
            if ($opCodes->isOp($opCode, 'OP_ROLL')) {
                $mainStack->erase($pos);
            }
            $mainStack->push($vch);
            return;
        } else if ($opName == 'OP_ROT') {
            if ($mainStack->size() < 3) {
                throw new \Exception('Invalid stack operation OP_ROT');
            }
            $mainStack->swap(-3, -2);
            $mainStack->swap(-2, -1);
            return;
        } else if ($opName == 'OP_SWAP') {
            if ($mainStack->size() < 2) {
                throw new \Exception('Invalid stack operation OP_SWAP');
            }
            $mainStack->swap(-2, -1);
            return;
        } else if ($opName == 'OP_TUCK') {
            if ($mainStack->size() < 2) {
                throw new \Exception('Invalid stack operation OP_TUCK');
            }
            $vch = $mainStack->top(-1);
            $mainStack->insert($mainStack->end() - 2, $vch);
            return;
        } else if ($opName == 'OP_2DROP') {
            if ($mainStack->size() < 2) {
                throw new \Exception('Invalid stack operation OP_2DROP');
            }
            $mainStack->pop();
            $mainStack->pop();
            return;
        } else if ($opName == 'OP_2DUP') {
            if ($mainStack->size() < 2) {
                throw new \Exception('Invalid stack operation OP_2DUP');
            }
            $string1 = $mainStack->top(-2);
            $string2 = $mainStack->top(-1);
            $mainStack->push($string1);
            $mainStack->push($string2);
            return;
        } else if ($opName == 'OP_3DUP') {
            if ($mainStack->size() < 3) {
                throw new \Exception('Invalid stack operation OP_3DUP');
            }
            $string1 = $mainStack->top(-3);
            $string2 = $mainStack->top(-2);
            $string3 = $mainStack->top(-1);
            $mainStack->push($string1);
            $mainStack->push($string2);
            $mainStack->push($string3);
            return;
        } else if ($opName == 'OP_2OVER') {
            if ($mainStack->size() < 4) {
                throw new \Exception('Invalid stack operation OP_2OVER');
            }
            $string1 = $mainStack->top(-4);
            $string2 = $mainStack->top(-3);
            $mainStack->push($string1);
            $mainStack->push($string2);
            return;
        } else if ($opName == 'OP_2ROT') {
            if ($mainStack->size() < 6) {
                throw new \Exception('Invalid stack operation OP_2ROT');
            }
            $string1 = $mainStack->top(-6);
            $string2 = $mainStack->top(-5);
            $mainStack->erase(-6);
            $mainStack->erase(-5);
            $mainStack->push($string1);
            $mainStack->push($string2);
            return;
        } else if ($opName == 'OP_2SWAP') {
            if ($mainStack->size() < 4) {
                throw new \Exception('Invalid stack operation OP_2SWAP');
            }
            $mainStack->swap(-3, -1);
            $mainStack->swap(-4, -2);
            return;
        }

        throw new \Exception('Opcode not found');
    }
}
