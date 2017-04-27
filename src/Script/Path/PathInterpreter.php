<?php

namespace BitWasp\Bitcoin\Script\Path;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Exceptions\ScriptRuntimeException;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Interpreter\Number;
use BitWasp\Bitcoin\Script\Interpreter\Stack;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class PathInterpreter
{

    /**
     * @var \BitWasp\Bitcoin\Math\Math
     */
    private $math;

    /**
     * @var BufferInterface
     */
    private $vchFalse;

    /**
     * @var BufferInterface
     */
    private $vchTrue;

    /**
     * @var array
     */
    private $disabledOps = [
        Opcodes::OP_CAT,    Opcodes::OP_SUBSTR, Opcodes::OP_LEFT,  Opcodes::OP_RIGHT,
        Opcodes::OP_INVERT, Opcodes::OP_AND,    Opcodes::OP_OR,    Opcodes::OP_XOR,
        Opcodes::OP_2MUL,   Opcodes::OP_2DIV,   Opcodes::OP_MUL,   Opcodes::OP_DIV,
        Opcodes::OP_MOD,    Opcodes::OP_LSHIFT, Opcodes::OP_RSHIFT
    ];

    /**
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $this->math = $ecAdapter->getMath();
        $this->vchFalse = new Buffer("", 0, $this->math);
        $this->vchTrue = new Buffer("\x01", 1, $this->math);
    }

    /**
     * Cast the value to a boolean
     *
     * @param BufferInterface $value
     * @return bool
     */
    public function castToBool(BufferInterface $value)
    {
        $val = $value->getBinary();
        for ($i = 0, $size = strlen($val); $i < $size; $i++) {
            $chr = ord($val[$i]);
            if ($chr !== 0) {
                if (($i === ($size - 1)) && $chr === 0x80) {
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @param int $opCode
     * @param BufferInterface $pushData
     * @return bool
     * @throws \Exception
     */
    public function checkMinimalPush($opCode, BufferInterface $pushData)
    {
        $pushSize = $pushData->getSize();
        $binary = $pushData->getBinary();

        if ($pushSize === 0) {
            return $opCode === Opcodes::OP_0;
        } elseif ($pushSize === 1) {
            $first = ord($binary[0]);

            if ($first >= 1 && $first <= 16) {
                return $opCode === (Opcodes::OP_1 + ($first - 1));
            } elseif ($first === 0x81) {
                return $opCode === Opcodes::OP_1NEGATE;
            }
        } elseif ($pushSize <= 75) {
            return $opCode === $pushSize;
        } elseif ($pushSize <= 255) {
            return $opCode === Opcodes::OP_PUSHDATA1;
        } elseif ($pushSize <= 65535) {
            return $opCode === Opcodes::OP_PUSHDATA2;
        }

        return true;
    }

    /**
     * @param Stack $vfStack
     * @param bool $value
     * @return bool
     */
    private function checkExec(Stack $vfStack, $value)
    {
        $ret = 0;
        foreach ($vfStack as $item) {
            if ($item === $value) {
                $ret++;
            }
        }

        return $ret;
    }

    /**
     * @param $bool
     * @return Buffer|BufferInterface
     */
    public function boolToVector($bool)
    {
        return $bool ? $this->vchTrue : $this->vchFalse;
    }

    /**
     * @param ScriptInterface $script
     * @param array $vfState
     * @param int $flags
     * @return array
     */
    public function getBranch(ScriptInterface $script, array $vfState, $flags)
    {
        $ops = [];
        foreach ($this->evaluate($script, $vfState, $flags) as $step) {
            if ($step[0]) {
                $ops[] = $step[1];
            }
        }

        return $ops;
    }

    /**
     * @param ScriptInterface $script
     * @param array $vfState
     * @param int $flags
     * @return array
     */
    public function evaluate(ScriptInterface $script, array $vfState, $flags)
    {
        $stack = new Stack();
        foreach ($vfState as $vfItem) {
            $stack->push($this->boolToVector($vfItem));
        }

        return $this->evaluateUsingStack($script, $stack, $flags);
    }

    /**
     * @param ScriptInterface $script
     * @param Stack $mainStack
     * @param int $flags
     * @return array
     * @throws ScriptRuntimeException
     */
    public function evaluateUsingStack(ScriptInterface $script, Stack $mainStack, $flags)
    {
        $vfStack = new Stack();
        $minimal = ($flags & Interpreter::VERIFY_MINIMALDATA) !== 0;
        $parser = $script->getScriptParser();

        $log = [];
        foreach ($parser as $i => $operation) {
            $opCode = $operation->getOp();
            $pushData = $operation->getData();
            $fExec = !$this->checkExec($vfStack, false);

            if ($operation->isPush() && $operation->getDataSize() > Interpreter::MAX_SCRIPT_ELEMENT_SIZE) {
                throw new \RuntimeException('Error - push size');
            }

            if (in_array($opCode, $this->disabledOps, true)) {
                throw new \RuntimeException('Disabled Opcode');
            }

            if ($fExec && $operation->isPush()) {
                if ($minimal && !$this->checkMinimalPush($opCode, $pushData)) {
                    throw new ScriptRuntimeException(Interpreter::VERIFY_MINIMALDATA, 'Minimal pushdata required');
                }

                $log[] = [$fExec, $operation];
            } elseif (Opcodes::OP_IF <= $opCode && $opCode <= Opcodes::OP_ENDIF) {
                $log[] = [$fExec, $operation];
                switch ($opCode) {
                    case Opcodes::OP_IF:
                    case Opcodes::OP_NOTIF:
                        // <expression> if [statements] [else [statements]] endif
                        $value = false;
                        if ($fExec) {
                            if ($mainStack->isEmpty()) {
                                throw new \RuntimeException('Unbalanced conditional');
                            }
                            $buffer = Number::buffer($mainStack->pop(), $minimal)->getBuffer();
                            $value = $this->castToBool($buffer);
                            if ($opCode === Opcodes::OP_NOTIF) {
                                $value = !$value;
                            }
                        }
                        $vfStack->push($value);
                        break;

                    case Opcodes::OP_ELSE:
                        if ($vfStack->isEmpty()) {
                            throw new \RuntimeException('Unbalanced conditional');
                        }
                        $vfStack->push(!$vfStack->pop());
                        break;

                    case Opcodes::OP_ENDIF:
                        if ($vfStack->isEmpty()) {
                            throw new \RuntimeException('Unbalanced conditional');
                        }
                        $vfStack->pop();
                        break;
                }
            } else if ($fExec) {
                //echo $script->getOpcodes()->getOp($opCode).PHP_EOL;
                $log[] = [$fExec, $operation];
            }
        }

        if (count($vfStack) !== 0) {
            throw new \RuntimeException('Unbalanced conditional at script end');
        }

        if (count($mainStack) !== 0) {
            print_r($mainStack->all());
            throw new \RuntimeException('Values remaining after script execution - invalid branch data');
        }

        return $log;
    }
}
